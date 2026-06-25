<?php

namespace App\Http\Ai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Repositories\ProductRepository;
use App\Services\SystemSettingService;
use Illuminate\Support\Facades\DB;

class CompareAi
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected SystemSettingService $settings
    ) {}

    public function compareProduct(int $productId)
    {
        try {
            if (!$this->settings->enabled(SystemSettingService::AI_PRODUCT_CHECK)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tính năng kiểm tra sản phẩm bằng AI hiện đang bị quản trị viên tắt.',
                ], 503);
            }

            if (!$this->productRepository->productExists($productId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại',
                ], 404);
            }

            $currentProduct = $this->productRepository->compareData($productId);

            if (!$currentProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm',
                ], 404);
            }

            $majorCode = strtolower(trim($currentProduct->major_name ?? ''));

            // Xác định loại project
            $projectType = $this->getProjectType($majorCode, $currentProduct);

            if (!$projectType) {
                return response()->json([
                    'success' => true,
                    'status' => false,
                    'message' => 'Loại sản phẩm không được hỗ trợ',
                    'current_product' => $this->formatProduct($currentProduct),
                    'matches' => [
                        'approved' => [],
                        'unapproved' => []
                    ],
                    'summary' => [
                        'match_count' => 0
                    ]
                ]);
            }

            $matchingProducts = $this->productRepository->findMatchingAiProducts($productId);
            $matchingProducts = array_slice($matchingProducts, 0, 5);

            $enriched = [];

            foreach ($matchingProducts as $product) {

                $gpt = $this->compareWithAi($currentProduct, $product, $projectType);
                $duplicateFields = $this->getDuplicateFields($currentProduct, $product, $projectType);

                $productId = (int) ($product['product_id'] ?? 0);

                $enriched[] = array_merge($product, [
                    'images' => $this->getProductImages($productId),

                    'ai_similarity' => $gpt['similarity'] ?? 0,
                    'ai_level' => $gpt['level'] ?? 'low',
                    'ai_reason' => $gpt['reason'] ?? '',
                    'has_duplicate_fields' => count($duplicateFields) > 0,
                    'duplicate_count' => count($duplicateFields),
                    'duplicate_fields' => $duplicateFields,
                    'duplicate_message' => count($duplicateFields) > 0
                        ? 'Trùng: ' . implode(', ', $duplicateFields)
                        : 'Không có trường chính trùng',
                ]);
            }

            $approved = array_values(array_filter($enriched, fn($p) => $p['status'] === 'approved'));
            $unapproved = array_values(array_filter($enriched, fn($p) => $p['status'] !== 'approved'));

            return response()->json([
                'success' => true,

                // QUAN TRỌNG
                'status' => count($enriched) > 0,

                'current_product' => $this->formatProduct($currentProduct),

                'matches' => [
                    'approved' => $approved,
                    'unapproved' => $unapproved,
                ],

                'summary' => [
                    'match_count' => count($enriched),
                    'approved_count' => count($approved),
                    'unapproved_count' => count($unapproved),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xác định loại project từ major_name
     * 4 loại: AI, CNTT, Multimedia, Graphics
     */
    private function getProjectType($majorCode, $currentProduct)
    {
        if (
            str_contains($majorCode, 'ai')
            || str_contains($majorCode, 'trí tuệ')
            || str_contains($majorCode, 'artificial')
        ) {
            return 'AI';
        }

        if (
            str_contains($majorCode, 'cntt')
            || str_contains($majorCode, 'computer')
            || str_contains($majorCode, 'công nghệ thông tin')
        ) {
            return ($currentProduct->programming_language ?? null)
                || ($currentProduct->framework ?? null) ? 'CNTT' : null;
        }

        if (
            str_contains($majorCode, 'multimedia')
            || str_contains($majorCode, 'mmt')
            || str_contains($majorCode, 'đa phương tiện')
            || str_contains($majorCode, 'mạng máy tính')
            || str_contains($majorCode, 'network')
        ) {
            return ($currentProduct->simulation_tool ?? null) ? 'Multimedia' : null;
        }

        if (
            str_contains($majorCode, 'graphics')
            || str_contains($majorCode, 'đồ họa')
            || str_contains($majorCode, 'graphic design')
            || str_contains($majorCode, 'thiết kế')
            || str_contains($majorCode, 'tkdh')
        ) {
            return 'Graphics';
        }

        return null;
    }

    /**
     * GPT similarity check với support cho 4 ngành
     * Return JSON bằng tiếng Việt
     */
    private function compareWithAi($a, $b, $projectType = 'AI')
    {
        try {

            $prompt = $this->buildComparisonPrompt($a, $b, $projectType);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert in comparing student projects for similarity and potential duplication. Respond ONLY with valid JSON, without any extra text. The "reason" field must be written in Vietnamese.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.2,
            ]);

            $content = $response->json()['choices'][0]['message']['content'] ?? null;

            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('GPT Compare Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build prompt động dựa trên loại project
     */
    private function buildComparisonPrompt($a, $b, $projectType)
    {
        $commonPrompt = "
        You are an expert in detecting similarity and potential duplication between student projects.

        Your task:
        - Compare the core idea, product goal, main features, and processing workflow.
        - Detect cases where a project only changes the name, UI, colors, logo, icons, layout, or minor details from an existing project.
        - Do not only compare words literally. You must understand the real meaning and purpose of each project.
        - Do not approve or reject the project. Only provide a similarity analysis and warning.

        COMPARISON PRIORITY:
        1. Core idea
        2. Product goal
        3. Main features
        4. Business workflow / processing flow
        5. System scope
        6. Target users
        7. Technology, framework, database, or tools

        SCORING WEIGHTS:
        - Core idea: 35%
        - Main features: 30%
        - Processing workflow: 20%
        - Technology / tools: 10%
        - Product name / UI: 5%

        SIMILARITY SCORING RULES:
        - 95-100: The projects are almost identical in idea, features, workflow, and technology.
        - 90-98: The project only changes the name, UI, colors, logo, icons, layout, or minor details, but the real product nature is still the same.
        - 80-89: The projects share the same core idea and most main features, but differ in some implementation details.
        - 60-79: The projects share some features or technology, but have different goals or scope.
        - Below 60: The projects are only in the same field/major, but their goals and features are clearly different.

        STRICT RULES:
        - If both projects have the same core idea and the same main features, similarity must be at least 80.
        - If only the product name is changed but the features and workflow are the same, similarity must be at least 90.
        - If the titles are different but the descriptions, features, and workflow are similar, the similarity must still be high.
        - Do not give a low similarity score only because the UI, colors, icons, layout, or images are different.
        - Do not give a low similarity score only because the technology stack is different, if the idea and features are similar.
        - If there is not enough data to compare, clearly mention it in the reason.
        - If similarity is 85 or above, level must be \"high\".
        - If similarity is from 60 to 84, level must be \"medium\".
        - If similarity is below 60, level must be \"low\".

        Return ONLY valid JSON in this format:
        {
            \"similarity\": number,
            \"level\": \"low\" | \"medium\" | \"high\" |  \"Vietnamese\",
            \"reason\": \"short explanation in Vietnamese\"
        }
        ";

        if ($projectType === 'AI') {
            return $commonPrompt . "
        Project A (AI):
        Title: {$a->title}
        Description: {$a->description}
        Model: {$a->model_used}
        Framework: {$a->framework}
        Programming language: {$a->language}
        Dataset: {$a->dataset_used}
        Accuracy score: {$a->accuracy_score}

        Project B (AI):
        Title: {$b['title']}
        Description: {$b['description']}
        Model: {$b['model_used']}
        Framework: {$b['framework']}
        Programming language: {$b['language']}
        Dataset: {$b['dataset_used']}
        Accuracy score: {$b['accuracy_score']}

        Compare based on: AI problem, core idea, model, dataset, framework, programming language, and implementation approach.
        ";
        } elseif ($projectType === 'CNTT') {
            return $commonPrompt . "
        Project A (Information Technology):
        Title: {$a->title}
        Description: {$a->description}
        Programming language: {$a->programming_language}
        Framework: {$a->framework}
        Database: {$a->database_used}

        Project B (Information Technology):
        Title: {$b['title']}
        Description: {$b['description']}
        Programming language: {$b['programming_language']}
        Framework: {$b['framework']}
        Database: {$b['database_used']}

        Compare based on: system idea, main features, processing workflow, target users, technology stack, framework, and database.
        ";
        } elseif ($projectType === 'Multimedia') {
            return $commonPrompt . "
        Project A (Multimedia / Networking):
        Title: {$a->title}
        Description: {$a->description}
        Simulation tool: {$a->simulation_tool}
        Network protocol: {$a->network_protocol}
        Topology type: {$a->topology_type}
        Config file: {$a->config_file}

        Project B (Multimedia / Networking):
        Title: {$b['title']}
        Description: {$b['description']}
        Simulation tool: {$b['simulation_tool']}
        Network protocol: {$b['network_protocol']}
        Topology type: {$b['topology_type']}
        Config file: {$b['config_file']}

        Compare based on: network/system model, topology, network protocol, simulation tool, configuration method, and implementation purpose.
        ";
        } elseif ($projectType === 'Graphics') {
            return $commonPrompt . "
        Project A (Graphic Design):
        Title: {$a->title}
        Description: {$a->description}
        Design type: {$a->design_type}
        Tools used: {$a->tools_used}
        Behance link: {$a->behance_link}

        Project B (Graphic Design):
        Title: {$b['title']}
        Description: {$b['description']}
        Design type: {$b['design_type']}
        Tools used: {$b['tools_used']}
        Behance link: {$b['behance_link']}

        Compare based on: design idea, visual style, layout, purpose, design type, tools, and overall creative direction.
        ";
        }

        return $commonPrompt . "
        Project A:
        Title: {$a->title}
        Description: {$a->description}

        Project B:
        Title: {$b['title']}
        Description: {$b['description']}

        Compare the similarity between these two student projects.
        ";
    }

    private function getDuplicateFields($a, $b, $projectType): array
    {
        $fieldsByType = [
            'AI' => [
                'title' => 'Tiêu đề',
                'model_used' => 'Model',
                'framework' => 'Framework',
                'language' => 'Ngôn ngữ',
                'dataset_used' => 'Dataset',
            ],

            'CNTT' => [
                'title' => 'Tiêu đề',
                'programming_language' => 'Ngôn ngữ lập trình',
                'framework' => 'Framework',
                'database_used' => 'Cơ sở dữ liệu',
            ],

            'Multimedia' => [
                'title' => 'Tiêu đề',
                'simulation_tool' => 'Công cụ mô phỏng',
                'network_protocol' => 'Giao thức mạng',
                'topology_type' => 'Loại hệ thống',
                'config_file' => 'File config',
            ],

            'Graphics' => [
                'title' => 'Tiêu đề',
                'description' => 'Mô tả',
                'design_type' => 'Loại thiết kế',
                'tools_used' => 'Công cụ sử dụng',
                'behance_link' => 'Link Behance',
            ],
        ];

        $fields = $fieldsByType[$projectType] ?? [
            'title' => 'Tiêu đề',
            'description' => 'Mô tả',
        ];

        $duplicated = [];

        foreach ($fields as $key => $label) {
            $valueA = $this->normalizeCompareValue($a->$key ?? '');
            $valueB = $this->normalizeCompareValue($b[$key] ?? '');

            if ($valueA !== '' && $valueA === $valueB) {
                $duplicated[] = $label;
            }
        }

        return $duplicated;
    }

    private function normalizeCompareValue($value): string
    {
        $value = trim(strtolower((string) $value));

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    private function formatProduct($p)
    {
        return [
            'product_id' => $p->product_id,
            'title' => $p->title,
            'description' => $p->description,
            'thumbnail' => $p->thumbnail,
            'images' => $this->getProductImages((int) $p->product_id),
            'status' => $p->status,
            'created_at' => $p->created_at,
            'approved_at' => $p->approved_at,
            'fullname' => $p->fullname,
            'major_name' => $p->major_name,
            // AI fields
            'model_used' => $p->model_used ?? null,
            'language' => $p->language ?? null,
            'dataset_used' => $p->dataset_used ?? null,
            'accuracy_score' => $p->accuracy_score ?? null,
            // CNTT fields
            'programming_language' => $p->programming_language ?? null,
            'database_used' => $p->database_used ?? null,
            // Multimedia fields
            'simulation_tool' => $p->simulation_tool ?? null,
            'network_protocol' => $p->network_protocol ?? null,
            'topology_type' => $p->topology_type ?? null,
            'config_file' => $p->config_file ?? null,
            // Graphics fields
            'design_type' => $p->design_type ?? null,
            'tools_used' => $p->tools_used ?? null,
            'behance_link' => $p->behance_link ?? null,
            // Common fields
            'framework' => $p->framework ?? null,
        ];
    }

    private function getProductImages(int $productId): array
    {
        if (!$productId) {
            return [];
        }

        return DB::table('product_images')
            ->where('product_id', $productId)
            ->whereNotNull('image_url')
            ->pluck('image_url')
            ->filter()
            ->values()
            ->all();
    }
}
