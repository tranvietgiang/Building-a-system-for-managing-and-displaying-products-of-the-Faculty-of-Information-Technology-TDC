<?php

namespace App\Http\Ai;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class ContentModeration
{
    public function moderateProduct(Product $product, array $frontendContext = []): array
    {
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            return $this->blocked('Missing OPENAI_API_KEY');
        }

        $major = DB::table('majors')
            ->where('major_id', $product->major_id)
            ->select('major_name', 'major_code')
            ->first();

        $imageUrl = $this->resolveImageUrl($product, $frontendContext);

        if (!$imageUrl) {
            return $this->blocked('No image found for moderation');
        }

        $payload = [
            'title' => $frontendContext['title'] ?? $product->title,
            'description' => $frontendContext['description'] ?? $product->description,
            'major' => $frontendContext['major']
                ?? $major?->major_name
                ?? $major?->major_code
                ?? 'Unknown',
            'image' => $imageUrl,
        ];

        $content = [
            [
                'type' => 'text',
                'text' => $this->buildPrompt($payload),
            ],
        ];

        // image (ONLY if valid url)
        if ($this->isSupportedImageReference($imageUrl)) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageUrl,
                ],
            ];
        }

        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'Return ONLY valid JSON. No explanation. No markdown.',
                ],
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('OPENAI_VISION_MODEL', 'gpt-4o-mini'),
                    'messages' => $messages,
                    'temperature' => 0.2,
                    'max_tokens' => 1000,
                ]);

            // ❌ REAL API ERROR
            if ($response->failed()) {
                $errorBody = $response->json();
                $errorMessage = $this->extractErrorMessage($errorBody);

                Log::error('OpenAI moderation API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'product_id' => $product->product_id,
                ]);

                return [
                    'approved' => false,
                    'reason' => 'Lỗi AI: ' . $errorMessage,
                    'violations' => ['api_error'],
                    'raw' => null,
                ];
            }

            $text = data_get($response->json(), 'choices.0.message.content');

            if (!$text) {
                return $this->blocked('Empty AI response');
            }

            $result = $this->parseJson($text);

            if (!$result) {
                Log::warning('Invalid AI JSON', [
                    'response' => $text,
                ]);

                return $this->blocked('Invalid AI response format');
            }

            $approved = (bool) ($result['approved'] ?? false);
            $violations = $this->formatViolations($result['violations'] ?? []);
            $reason = trim($result['reason'] ?? '');

            //  reduce false positive (important)
            if (!$approved && count($violations) === 0) {
                return [
                    'approved' => true,
                    'reason' => 'Auto-approved (no strong violations)',
                    'violations' => [],
                    'raw' => $result,
                ];
            }

            return [
                'approved' => $approved,
                'reason' => $reason ?: ($approved ? 'OK' : 'Rejected by AI'),
                'violations' => $violations,
                'raw' => $result,
            ];
        } catch (\Throwable $e) {
            Log::error('AI moderation exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->blocked($e->getMessage());
        }
    }

    public function moderateUploadedImage(UploadedFile $image, array $frontendContext = []): array
    {
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            return $this->blocked('Missing OPENAI_API_KEY');
        }

        $base64 = base64_encode(file_get_contents($image->getRealPath()));
        $mime = $image->getMimeType();

        $imageUrl = "data:{$mime};base64,{$base64}";

        $payload = [
            'title' => $frontendContext['title'] ?? '',
            'description' => $frontendContext['description'] ?? '',
            'major' => $frontendContext['major'] ?? 'Unknown',
            'image' => 'uploaded_image',
        ];

        $content = [
            [
                'type' => 'text',
                'text' => $this->buildPrompt($payload),
            ],
            [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageUrl,
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('OPENAI_VISION_MODEL', 'gpt-4o-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Return ONLY valid JSON. No explanation. No markdown.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $content,
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 1000,
                ]);

            if ($response->failed()) {
                return [
                    'approved' => false,
                    'reason' => 'Lỗi AI: ' . $response->body(),
                    'violations' => ['api_error'],
                    'raw' => null,
                ];
            }

            $text = data_get($response->json(), 'choices.0.message.content');

            if (!$text) {
                return $this->blocked('Empty AI response');
            }

            $result = $this->parseJson($text);

            if (!$result) {
                return $this->blocked('Invalid AI response format');
            }

            return [
                'approved' => (bool) ($result['approved'] ?? false),
                'reason' => trim($result['reason'] ?? ''),
                'violations' => $this->formatViolations($result['violations'] ?? []),
                'raw' => $result,
            ];
        } catch (\Throwable $e) {
            return $this->blocked($e->getMessage());
        }
    }

    private function parseJson(string $text): ?array
    {
        $text = trim($text);

        // remove markdown
        $text = preg_replace('/```json|```/', '', $text);

        $decoded = json_decode($text, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function formatViolations($violations): array
    {
        if (!is_array($violations)) {
            $violations = [$violations];
        }

        return array_values(array_filter(array_map(function ($violation) {
            if (is_array($violation)) {
                $source = $violation['source'] ?? $violation['type'] ?? null;
                $content = $violation['content'] ?? $violation['text'] ?? $violation['detail'] ?? null;
                $reason = $violation['reason'] ?? $violation['message'] ?? null;

                $parts = array_values(array_filter([$content, $reason]));
                $text = implode(' - ', $parts);

                if ($source && str_contains(strtolower((string) $source), 'image')) {
                    $text = 'Ảnh: ' . $text;
                }

                return trim($text ?: json_encode($violation, JSON_UNESCAPED_UNICODE));
            }

            return trim((string) $violation);
        }, $violations)));
    }

    private function resolveImageUrl(Product $product, array $frontendContext): ?string
    {
        $imageUrl = $frontendContext['image']
            ?? $frontendContext['thumbnail']
            ?? $product->thumbnail;

        if (!$imageUrl) {
            $imageUrl = DB::table('product_images')
                ->where('product_id', $product->product_id)
                ->value('image_url');
        }

        if (!$imageUrl) {
            return null;
        }

        $imageUrl = trim($imageUrl);

        if (Str::startsWith($imageUrl, ['http://', 'https://', 'data:image/'])) {
            return $imageUrl;
        }

        $appUrl = (string) config('app.url', '');

        return rtrim($appUrl, '/') . '/' . ltrim($imageUrl, '/');
    }

    private function isSupportedImageReference(string $imageUrl): bool
    {
        return Str::startsWith($imageUrl, ['http://', 'https://', 'data:image/']);
    }

    /**
     * Extract readable error message from OpenAI API error response
     * Chuyển lỗi API thành thông báo dễ hiểu bằng tiếng Việt
     */
    private function extractErrorMessage(array $errorBody): string
    {
        // Check standard OpenAI error format: error.message
        if (isset($errorBody['error']['message'])) {
            $msg = $errorBody['error']['message'];

            // Translate common error messages to Vietnamese
            if (strpos($msg, 'invalid_image_url') !== false || strpos($msg, 'downloading') !== false) {
                return 'Lỗi ảnh: URL ảnh không hợp lệ hoặc không thể tải xuống';
            }
            if (strpos($msg, 'timeout') !== false) {
                return 'Lỗi: Yêu cầu quá thời gian chờ';
            }
            if (strpos($msg, 'rate_limit') !== false) {
                return 'Lỗi: Quá nhiều yêu cầu, vui lòng thử lại sau';
            }
            if (strpos($msg, 'authentication') !== false || strpos($msg, 'unauthorized') !== false) {
                return 'Lỗi: Xác thực không hợp lệ';
            }
            if (strpos($msg, 'unsupported_image_format') !== false) {
                return 'Lỗi ảnh: Định dạng ảnh không được hỗ trợ';
            }

            // Return original message if no translation
            return $msg;
        }

        // Fallback to generic error message
        return 'Lỗi hệ thống AI: vui lòng thử lại sau';
    }

    private function blocked(string $reason): array
    {
        return [
            'approved' => false,
            'reason' => $reason,
            'violations' => [$reason],
            'raw' => null,
        ];
    }

    private function buildPrompt(array $payload, string $role = 'student'): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
        Bạn là hệ thống AI kiểm duyệt nội dung cho nền tảng nghiên cứu khoa học sinh viên.

        Vai trò người dùng: {$role}

        QUY TẮC THEO VAI TRÒ:
        - student: kiểm duyệt nghiêm ngặt, chặn ngay nội dung nhạy cảm, không an toàn, 
        spam, ảnh chế, nội dung không liên quan học thuật hoặc có dấu hiệu sao chép

        - teacher: linh hoạt hơn với nội dung mang tính giáo dục hoặc minh họa học thuật, 
        nhưng vẫn phải chặn nội dung 18+, bạo lực, bất hợp pháp,nội dung gây nguy hiểm, 
        watermark nặng hoặc có dấu hiệu đánh cắp

        Nhiệm vụ:
        - Phân tích hình ảnh và nội dung sản phẩm
        - Kiểm tra mức độ phù hợp với môi trường giáo dục và nghiên cứu
        - Kiểm tra nội dung 18+ / khỏa thân / tình dục
        - Kiểm tra nội dung bạo lực / nguy hiểm / phản cảm
        - Kiểm tra spam / ảnh chế / nội dung chất lượng thấp
        - Kiểm tra độ liên quan với chuyên ngành hoặc lĩnh vực học thuật
        - Kiểm tra watermark hoặc dấu hiệu nội dung bị sao chép / đánh cắp
        - Kiểm tra nội dung gây hiểu lầm, thông tin sai lệch hoặc phi học thuật
        - Kiểm tra ngôn từ thô tục, xúc phạm hoặc thiếu văn minh
        - Kiểm tra hình ảnh mờ, chất lượng thấp hoặc không liên quan sản phẩm
        - Kiểm tra dấu hiệu quảng cáo, câu view hoặc nội dung giải trí không phù hợp
        - Kiểm tra mức độ chuyên nghiệp và tính nghiêm túc của nội dung
        - Kiểm tra nội dung có vi phạm pháp luật hoặc đạo đức học thuật hay không
        - Kiểm tra nội dung có mang tính phân biệt đối xử, kích động hoặc gây tranh cãi không phù hợp

        Dữ liệu sản phẩm:
        {$json}

        QUY TẮC QUAN TRỌNG:
        - Nếu role = student → chấm điểm nghiêm ngặt hơn
        - Nếu role = teacher → cho phép một số nội dung giáo dục ở mức ranh giới
        - Chỉ trả về JSON hợp lệ
        - Không giải thích ngoài JSON
        - Trường "violations" phải là mảng các chuỗi tiếng Việt mô tả cụ thể nội dung vi phạm
        - Nếu vi phạm nằm trong hình ảnh, mỗi phần tử violations phải bắt đầu bằng "Ảnh:" và ghi rõ nội dung nhìn thấy trong ảnh
        - Nếu trong ảnh có chữ, logo, watermark, thông tin nhạy cảm hoặc nội dung gây vi phạm, hãy ghi lại ngắn gọn chính thông tin/chữ đó trong violations
        - Không trả violations chung chung như "image_related" hoặc "adult_or_sensitive"; phải ghi rõ ví dụ: "Ảnh: có chữ ...", "Ảnh: có watermark ...", "Ảnh: nội dung không liên quan ..."

        Định dạng trả về:

        {
            "approved": true,
            "score": 0-100,
            "reason": "giải thích ngắn gọn bằng tiếng Việt",
            "violations": [],
            "role": "{$role}",
            "checks": {
                "image_related": true,
                "educational": true,
                "adult_or_sensitive": false,
                "violence_or_danger": false,
                "spam_or_meme": false,
                "major_match": true,
                "watermark_or_stolen_signal": false
            }
        }

        Từ chối (approved=false) nếu:
        - Phát hiện nội dung 18+ / tình dục
        - Phát hiện bạo lực / máu me
        - Có watermark nặng hoặc dấu hiệu nội dung bị đánh cắp
        PROMPT;
    }
}
