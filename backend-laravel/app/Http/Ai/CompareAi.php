<?php

namespace App\Http\Ai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Repositories\ProductRepository;

class CompareAi
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    public function compareProduct(int $productId)
    {
        try {

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

                $enriched[] = array_merge($product, [
                    'ai_similarity' => $gpt['similarity'] ?? 0,
                    'ai_level' => $gpt['level'] ?? 'low',
                    'ai_reason' => $gpt['reason'] ?? '',
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
            return $currentProduct->model_used ? 'AI' : null;
        }

        if (
            str_contains($majorCode, 'cntt')
            || str_contains($majorCode, 'computer')
            || str_contains($majorCode, 'công nghệ thông tin')
        ) {
            return $currentProduct->programming_language || $currentProduct->framework ? 'CNTT' : null;
        }

        if (
            str_contains($majorCode, 'multimedia')
            || str_contains($majorCode, 'mmt')
            || str_contains($majorCode, 'đa phương tiện')
        ) {
            return $currentProduct->simulation_tool ? 'Multimedia' : null;
        }

        if (
            str_contains($majorCode, 'graphics')
            || str_contains($majorCode, 'đồ họa')
            || str_contains($majorCode, 'graphic design')
        ) {
            return $currentProduct->design_type || $currentProduct->tools_used ? 'Graphics' : null;
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
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Bạn là một chuyên gia so sánh dự án sinh viên. Trả lời CHỈ bằng JSON hợp lệ, không có text khác. Tất cả trường "reason" phải bằng tiếng Việt.'
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
So sánh 2 dự án sinh viên.

Trả lời JSON ONLY:
{
    \"similarity\": number (0-100),
    \"level\": \"low\" | \"medium\" | \"high\",
    \"reason\": \"giải thích ngắn bằng tiếng Việt\"
}
";

        if ($projectType === 'AI') {
            return $commonPrompt . "
Dự án A (AI):
Tiêu đề: {$a->title}
Model: {$a->model_used}
Framework: {$a->framework}
Ngôn ngữ: {$a->language}
Dataset: {$a->dataset_used}
Độ chính xác: {$a->accuracy_score}

Dự án B (AI):
Tiêu đề: {$b['title']}
Model: {$b['model_used']}
Framework: {$b['framework']}
Ngôn ngữ: {$b['language']}
Dataset: {$b['dataset_used']}
Độ chính xác: {$b['accuracy_score']}

So sánh dựa trên: Model, Framework, Ngôn ngữ, Dataset sử dụng.
";
        } elseif ($projectType === 'CNTT') {
            return $commonPrompt . "
Dự án A (CNTT):
Tiêu đề: {$a->title}
Ngôn ngữ lập trình: {$a->programming_language}
Framework: {$a->framework}
Cơ sở dữ liệu: {$a->database_used}

Dự án B (CNTT):
Tiêu đề: {$b['title']}
Ngôn ngữ lập trình: {$b['programming_language']}
Framework: {$b['framework']}
Cơ sở dữ liệu: {$b['database_used']}

So sánh dựa trên: Ngôn ngữ lập trình, Framework, Cơ sở dữ liệu.
";
        } elseif ($projectType === 'Multimedia') {
            return $commonPrompt . "
Dự án A (Multimedia):
Tiêu đề: {$a->title}
Công cụ mô phỏng: {$a->simulation_tool}
Giao thức mạng: {$a->network_protocol}
Loại hệ thống: {$a->topology_type}
File config: {$a->config_file}

Dự án B (Multimedia):
Tiêu đề: {$b['title']}
Công cụ mô phỏng: {$b['simulation_tool']}
Giao thức mạng: {$b['network_protocol']}
Loại hệ thống: {$b['topology_type']}
File config: {$b['config_file']}

So sánh dựa trên: Công cụ mô phỏng, Giao thức mạng, Loại hệ thống.
";
        } elseif ($projectType === 'Graphics') {
            return $commonPrompt . "
Dự án A (Đồ họa):
Tiêu đề: {$a->title}
Loại thiết kế: {$a->design_type}
Công cụ sử dụng: {$a->tools_used}
Link Drive: {$a->drive_link}
Link Behance: {$a->behance_link}

Dự án B (Đồ họa):
Tiêu đề: {$b['title']}
Loại thiết kế: {$b['design_type']}
Công cụ sử dụng: {$b['tools_used']}
Link Drive: {$b['drive_link']}
Link Behance: {$b['behance_link']}

So sánh dựa trên: Loại thiết kế, Công cụ sử dụng, Phong cách thiết kế.
";
        }

        // Default for other types
        return $commonPrompt . "
Dự án A:
Tiêu đề: {$a->title}
Mô tả: {$a->description}

Dự án B:
Tiêu đề: {$b['title']}
Mô tả: {$b['description']}

So sánh độ tương đồng giữa 2 dự án này.
";
    }

    private function formatProduct($p)
    {
        return [
            'product_id' => $p->product_id,
            'title' => $p->title,
            'description' => $p->description,
            'thumbnail' => $p->thumbnail,
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
            'drive_link' => $p->drive_link ?? null,
            'behance_link' => $p->behance_link ?? null,
            // Common fields
            'framework' => $p->framework ?? null,
        ];
    }
}
