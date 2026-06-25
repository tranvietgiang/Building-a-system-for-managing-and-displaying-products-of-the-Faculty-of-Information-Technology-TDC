<?php

namespace App\Http\Ai;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use App\Services\SystemSettingService;

class ContentModeration
{
    public function __construct(
        protected SystemSettingService $settings
    ) {}

    public function moderateProduct(Product $product, array $frontendContext = []): array
    {
        if (!$this->settings->enabled(SystemSettingService::AI_PRODUCT_CHECK)) {
            return $this->skipped('AI product checking disabled');
        }

        $apiKey = config('services.openai.key');

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
                    'model' => config('services.openai.vision_model', 'gpt-4o-mini'),
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

            return $this->normalizeModerationResult($result, $payload);
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
        if (!$this->settings->enabled(SystemSettingService::AI_PRODUCT_CHECK)) {
            return $this->skipped('AI product checking disabled');
        }

        $apiKey = config('services.openai.key');

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
                    'model' => config('services.openai.vision_model', 'gpt-4o-mini'),
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

            return $this->normalizeModerationResult($result, $payload);
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

    private function normalizeModerationResult(array $result, array $payload = []): array
    {
        $approved = (bool) ($result['approved'] ?? false);
        $violations = $this->formatViolations($result['violations'] ?? []);
        $reason = trim($result['reason'] ?? '');

        if (!$approved && $this->isSafeEducationalSoftwareScreenshot($result, $violations)) {
            return [
                'approved' => true,
                'reason' => 'Ảnh giao diện phần mềm giáo dục có dữ liệu minh họa hợp lệ',
                'violations' => [],
                'raw' => $result,
            ];
        }

        if (!$approved && $this->isSafeCreativeDesignWork($result, $violations, $payload)) {
            return [
                'approved' => true,
                'reason' => 'Ảnh minh họa thiết kế hoặc thời trang hợp lệ',
                'violations' => [],
                'raw' => $result,
            ];
        }

        // Giảm false-positive khi AI từ chối nhưng không nêu được vi phạm cụ thể.
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
    }

    private function isSafeEducationalSoftwareScreenshot(array $result, array $violations): bool
    {
        $checks = $result['checks'] ?? [];

        if (($checks['software_ui_or_prototype'] ?? false) !== true
            || ($checks['image_related'] ?? false) !== true
            || ($checks['major_match'] ?? false) !== true
        ) {
            return false;
        }

        foreach (['adult_or_sensitive', 'violence_or_danger', 'spam_or_meme', 'watermark_or_stolen_signal'] as $check) {
            if (($checks[$check] ?? false) === true) {
                return false;
            }
        }

        $allowedPrivacyTerms = [
            'sinh viên',
            'học viên',
            'thí sinh',
            'mssv',
            'mã sinh viên',
            'mã học viên',
            'điểm số',
            'bảng điểm',
            'ngày sinh',
            'địa chỉ',
            'họ và tên',
            'thông tin cá nhân',
            'thông tin nhạy cảm',
        ];

        return count($violations) > 0 && collect($violations)->every(function ($violation) use ($allowedPrivacyTerms) {
            $text = Str::lower($violation);
            return collect($allowedPrivacyTerms)->contains(fn($term) => str_contains($text, $term));
        });
    }

    private function isSafeCreativeDesignWork(array $result, array $violations, array $payload): bool
    {
        $major = Str::lower((string) ($payload['major'] ?? ''));
        $isGraphicMajor = collect([
            'tkdh',
            'đồ họa',
            'thiết kế',
            'graphic',
            'design',
        ])->contains(fn($term) => str_contains($major, $term));

        if (!$isGraphicMajor) {
            return false;
        }

        $content = Str::lower(implode(' ', [
            (string) ($result['reason'] ?? ''),
            implode(' ', $violations),
        ]));

        $strongViolations = [
            'khỏa thân',
            'khoa than',
            'nudity',
            ' nude ',
            'bộ phận sinh dục',
            'lộ ngực',
            'ngực trần',
            'tình dục',
            'sexual',
            'porn',
            'khiêu dâm',
            'đồ lót gợi dục',
            'lingerie',
            'bikini',
            'máu me',
            'thi thể',
            'chặt đầu',
            'vũ khí',
            'weapon',
            'ma túy',
            'drug',
            'tự sát',
            'suicide',
        ];

        return !collect($strongViolations)
            ->contains(fn($term) => str_contains(" {$content} ", $term));
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

    private function skipped(string $reason): array
    {
        return [
            'approved' => true,
            'reason' => $reason,
            'violations' => [],
            'raw' => null,
            'skipped' => true,
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
        - Kiểm tra hình ảnh có liên quan đến sản phẩm nghiên cứu/chuyên ngành hay không
        - Phát hiện nội dung 18+, khỏa thân, tình dục
        - Phát hiện nội dung bạo lực, máu me, nguy hiểm
        - Phát hiện nội dung phản cảm, kích động, vi phạm pháp luật hoặc phân biệt đối xử
        - Kiểm tra spam, ảnh chế, nội dung chất lượng thấp hoặc không phù hợp học thuật

        Dữ liệu sản phẩm:
        {$json}

        QUY TẮC QUAN TRỌNG:

        - Nếu role = student → ưu tiên cho phép các nội dung phục vụ học tập và nghiên cứu
        - Chỉ trả về JSON hợp lệ
        - Không giải thích ngoài JSON
        - Trường "violations" phải mô tả cụ thể nội dung vi phạm bằng tiếng Việt
        - Nếu vi phạm nằm trong hình ảnh, mỗi phần tử trong "violations" phải bắt đầu bằng "Ảnh:" và mô tả rõ nội dung vi phạm
        - Không sử dụng các mô tả chung chung như "adult_or_sensitive" hoặc "image_related"
        - Ảnh chụp giao diện website, ứng dụng, prototype hoặc bài tập được xem là nội dung học thuật hợp lệ
        - Không từ chối chỉ vì hình ảnh có logo, watermark nhỏ hoặc giao diện tham khảo
        - Chỉ từ chối khi phát hiện nội dung 18+, bạo lực, phản cảm, spam hoặc hình ảnh hoàn toàn không liên quan đến sản phẩm
        - Nếu không đủ căn cứ xác định vi phạm, ưu tiên đánh giá an toàn và giải thích trong trường "reason"

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
                "watermark_or_stolen_signal": false,
                "software_ui_or_prototype": false
            }
        }

        Từ chối (approved=false) nếu:
        - Có nội dung 18+, khỏa thân hoặc tình dục
        - Có nội dung bạo lực, máu me hoặc nguy hiểm
        - Có nội dung phản cảm hoặc vi phạm pháp luật
        - Là hình ảnh spam, ảnh chế hoặc hoàn toàn không liên quan đến sản phẩm
        PROMPT;
    }
}
