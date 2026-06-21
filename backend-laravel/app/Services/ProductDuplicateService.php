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

        if (!$majorId || $title === '') {
            return null;
        }

        $exactMatch = DB::table('products')
            ->where('major_id', $majorId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereRaw('LOWER(TRIM(title)) = ?', [mb_strtolower($title)])
            ->latest('product_id')
            ->first(['product_id', 'title', 'description', 'status']);

        if ($exactMatch) {
            return [
                'product_id' => (int) $exactMatch->product_id,
                'title' => $exactMatch->title,
                'similarity' => 100,
                'reason' => 'Tên sản phẩm trùng hoàn toàn với sản phẩm đã tồn tại.',
                'method' => 'exact',
            ];
        }

        return $this->checkWithAi($data, $majorId);
    }

    private function checkWithAi(array $data, int $majorId): ?array
    {
        $apiKey = config('services.openai.key');

        if (!$apiKey) {
            return null;
        }

        $candidates = DB::table('products')
            ->where('major_id', $majorId)
            ->whereIn('status', ['pending', 'approved'])
            ->latest('product_id')
            ->limit(10)
            ->get(['product_id', 'title', 'description'])
            ->map(fn ($product) => (array) $product)
            ->all();

        if ($candidates === []) {
            return null;
        }

        try {
            $response = Http::timeout(25)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.vision_model', 'gpt-4o-mini'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Bạn phát hiện dự án sinh viên trùng lặp. Chỉ trả JSON: {"duplicate":boolean,"product_id":number|null,"similarity":number,"reason":string}. Chỉ đánh duplicate=true khi nội dung/công nghệ gần như cùng một sản phẩm, similarity từ 90 trở lên.',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'new_product' => $this->comparableData($data),
                                'existing_products' => $candidates,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                    'temperature' => 0.1,
                ]);

            if (!$response->successful()) {
                Log::warning('Duplicate AI check failed', ['status' => $response->status()]);
                return null;
            }

            $result = json_decode(
                (string) $response->json('choices.0.message.content'),
                true
            );
            $threshold = (int) config('product.duplicate_similarity_threshold', 90);
            $matchedId = (int) ($result['product_id'] ?? 0);
            $candidate = collect($candidates)->firstWhere('product_id', $matchedId);

            if (empty($result['duplicate']) || (int) ($result['similarity'] ?? 0) < $threshold || !$candidate) {
                return null;
            }

            return [
                'product_id' => $matchedId,
                'title' => $candidate['title'],
                'similarity' => (int) $result['similarity'],
                'reason' => $result['reason'] ?? 'AI phát hiện sản phẩm có nội dung gần như trùng lặp.',
                'method' => 'ai',
            ];
        } catch (\Throwable $exception) {
            Log::error('Duplicate AI check error: '.$exception->getMessage());
            return null;
        }
    }

    private function comparableData(array $data): array
    {
        return collect($data)->only([
            'title', 'description', 'major_code', 'programming_language',
            'framework', 'database_used', 'model_used', 'language',
            'dataset_used', 'simulation_tool', 'network_protocol',
            'topology_type', 'design_type', 'tools_used',
        ])->all();
    }
}
