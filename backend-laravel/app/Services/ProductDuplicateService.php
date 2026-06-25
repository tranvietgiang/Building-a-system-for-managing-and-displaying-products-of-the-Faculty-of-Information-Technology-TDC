<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductDuplicateService
{
    public function check(array $data): ?array
    {
        $majorId = (int) ($data['major_id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $excludedProductId = (int) ($data['replace_product_id'] ?? 0);

        if (!$majorId || $title === '') {
            return null;
        }

        /**
         * Không còn chặn chỉ vì trùng title.
         * Chỉ cảnh báo nếu title giống và description cũng rất giống.
         */
        $sameTitleProducts = DB::table('products')
            ->where('major_id', $majorId)
            ->when($excludedProductId, fn($query) => $query->where('product_id', '!=', $excludedProductId))
            ->whereIn('status', ['pending', 'approved'])
            ->whereRaw('LOWER(TRIM(title)) = ?', [mb_strtolower($title)])
            ->latest('product_id')
            ->limit(5)
            ->get(['product_id', 'title', 'description', 'status']);

        foreach ($sameTitleProducts as $product) {
            $descriptionSimilarity = $this->textSimilarityPercent(
                $description,
                (string) $product->description
            );

            if ($descriptionSimilarity >= 90) {
                return [
                    'product_id' => (int) $product->product_id,
                    'title' => $product->title,
                    'similarity' => $descriptionSimilarity,
                    'reason' => 'Tên sản phẩm và mô tả gần như trùng với sản phẩm đã tồn tại.',
                    'method' => 'local_title_description',
                ];
            }
        }

        return $this->checkWithAi($data, $majorId, $excludedProductId);
    }

    private function checkWithAi(array $data, int $majorId, int $excludedProductId = 0): ?array
    {
        $apiKey = config('services.openai.key');

        if (!$apiKey) {
            return null;
        }

        $candidates = DB::table('products')
            ->where('major_id', $majorId)
            ->when($excludedProductId, fn($query) => $query->where('product_id', '!=', $excludedProductId))
            ->whereIn('status', ['pending', 'approved'])
            ->latest('product_id')
            ->limit(10)
            ->get([
                'product_id',
                'title',
                'description',
                'status',
            ])
            ->map(fn($product) => (array) $product)
            ->all();

        if ($candidates === []) {
            return null;
        }

        try {
            $response = Http::timeout(25)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.text_model', 'gpt-4o-mini'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => '
You are an AI system that checks whether a new student project is nearly duplicated from existing student projects.

Return ONLY valid JSON:
{
  "duplicate": boolean,
  "product_id": number|null,
  "similarity": number,
  "reason": string
}

The "reason" field must be written in Vietnamese.

IMPORTANT RULES:
- Do NOT mark duplicate only because projects use the same programming language, framework, database, tool, or major.
- Do NOT mark duplicate only because both projects are websites, mobile apps, dashboards, management systems, booking systems, or e-commerce systems.
- Do NOT mark duplicate only because the title is similar.
- Mark duplicate=true ONLY when the new project is almost the same product as an existing one.
- Duplicate=true should be used only when the core idea, main features, workflow, target users, and implementation scope are almost identical.
- If projects are only in the same field but have different purpose, features, or workflow, duplicate must be false.
- If similarity is below 95, duplicate must be false.
- Only return duplicate=true when similarity is from 95 to 100.
- Similarity 90-94 means high similarity but still not enough to block submission.
- Similarity below 90 means not a duplicate.

Scoring guide:
- 95-100: Almost the same product, only minor wording/UI/name changes.
- 90-94: Very similar idea, but still has some meaningful differences. Return duplicate=false.
- 70-89: Same field or some similar features, but not a duplicate.
- Below 70: Clearly different.
                            ',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'new_product' => $this->comparableData($data),
                                'existing_products' => $candidates,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                    'temperature' => 0,
                    'max_tokens' => 500,
                ]);

            if (!$response->successful()) {
                Log::warning('Duplicate AI check failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $result = json_decode(
                (string) $response->json('choices.0.message.content'),
                true
            );

            if (!is_array($result)) {
                return null;
            }

            /**
             * Nới ngưỡng mặc định lên 95.
             * Có thể chỉnh trong config/product.php nếu muốn.
             */
            $threshold = (int) config('product.duplicate_similarity_threshold', 95);

            $matchedId = (int) ($result['product_id'] ?? 0);
            $similarity = (int) ($result['similarity'] ?? 0);

            $candidate = collect($candidates)->firstWhere('product_id', $matchedId);

            if (
                empty($result['duplicate'])
                || $similarity < $threshold
                || !$candidate
            ) {
                return null;
            }

            return [
                'product_id' => $matchedId,
                'title' => $candidate['title'],
                'similarity' => $similarity,
                'reason' => $result['reason'] ?? 'AI phát hiện sản phẩm gần như trùng với một sản phẩm đã tồn tại.',
                'method' => 'ai',
            ];
        } catch (\Throwable $exception) {
            Log::error('Duplicate AI check error: ' . $exception->getMessage());
            return null;
        }
    }

    private function comparableData(array $data): array
    {
        return collect($data)->only([
            'title',
            'description',
            'major_code',
            'programming_language',
            'framework',
            'database_used',
            'model_used',
            'language',
            'dataset_used',
            'simulation_tool',
            'network_protocol',
            'topology_type',
            'design_type',
            'tools_used',
        ])->all();
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/u', ' ', $text);

        return $text ?? '';
    }

    private function textSimilarityPercent(string $a, string $b): int
    {
        $a = $this->normalizeText($a);
        $b = $this->normalizeText($b);

        if ($a === '' || $b === '') {
            return 0;
        }

        similar_text($a, $b, $percent);

        return (int) round($percent);
    }
}
