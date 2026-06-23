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

        if (!$approved && !$this->hasStrongModerationViolation($result, $violations)) {
            return [
                'approved' => true,
                'reason' => 'Auto-approved (no strong safety violations)',
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

    private function hasStrongModerationViolation(array $result, array $violations): bool
    {
        $checks = $result['checks'] ?? [];

        foreach ([
            'adult_or_sensitive',
            'violence_or_danger',
            'spam_or_meme',
            'illegal_or_unethical',
            'discrimination_or_incitation',
            'clickbait_or_ads',
            'low_quality_or_unprofessional',
        ] as $key) {
            if (($checks[$key] ?? false) === true) {
                return true;
            }
        }

        $content = Str::lower(implode(' ', [
            (string) ($result['reason'] ?? ''),
            implode(' ', $violations),
        ]));

        $strongTerms = [
            '18+',
            'khỏa thân',
            'khoa than',
            'nudity',
            'tình dục',
            'tinh duc',
            'sexual',
            'porn',
            'khiêu dâm',
            'khieu dam',
            'bạo lực',
            'bao luc',
            'violence',
            'máu me',
            'mau me',
            'vũ khí',
            'vu khi',
            'weapon',
            'nguy hiểm',
            'nguy hiem',
            'danger',
            'phản cảm',
            'phan cam',
            'spam',
            'ảnh chế',
            'anh che',
            'meme',
            'câu view',
            'cau view',
            'clickbait',
            'quảng cáo',
            'quang cao',
            'bất hợp pháp',
            'bat hop phap',
            'illegal',
            'vi phạm pháp luật',
            'vi pham phap luat',
            'đạo đức học thuật',
            'dao duc hoc thuat',
            'phân biệt đối xử',
            'phan biet doi xu',
            'kích động',
            'kich dong',
            'thù ghét',
            'thu ghet',
            'hate',
        ];

        return collect($strongTerms)
            ->contains(fn($term) => str_contains(" {$content} ", $term));
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
        Bạn là hệ thống AI kiểm duyệt nội dung khi sinh viên đăng sản phẩm nghiên cứu.

        Mục tiêu của bước này là kiểm tra an toàn nội dung cơ bản. KHÔNG đánh giá trùng lặp sản phẩm, KHÔNG chấm quá kỹ chất lượng học thuật, KHÔNG từ chối chỉ vì sản phẩm đơn giản, ảnh UI chưa đẹp, ảnh demo chưa hoàn hảo hoặc nội dung chưa thật chuyên sâu. Chức năng so sánh trùng sẽ do giáo viên và module so sánh riêng xử lý.

        Vai trò người dùng: {$role}

        Chỉ kiểm tra các nhóm sau:
        - Mức độ phù hợp tối thiểu với môi trường giáo dục và nghiên cứu.
        - Nội dung 18+, khỏa thân, tình dục, khiêu dâm.
        - Nội dung bạo lực, nguy hiểm, máu me, phản cảm.
        - Spam, ảnh chế, nội dung rác, nội dung chất lượng quá thấp rõ ràng.
        - Dấu hiệu quảng cáo, câu view hoặc nội dung giải trí không phù hợp với môi trường học thuật.
        - Mức độ chuyên nghiệp và tính nghiêm túc ở mức tối thiểu.
        - Nội dung vi phạm pháp luật hoặc đạo đức học thuật rõ ràng.
        - Nội dung phân biệt đối xử, kích động, thù ghét hoặc gây tranh cãi không phù hợp.

        Không kiểm tra:
        - Không kiểm tra sản phẩm có trùng hay không.
        - Không kiểm tra watermark hoặc bản quyền nếu chỉ là logo, mockup, poster, giao diện, thương hiệu minh họa trong bài làm sinh viên.
        - Không từ chối vì ảnh là giao diện phần mềm demo, prototype, dashboard, sơ đồ mạng, poster, mockup thương hiệu hoặc sản phẩm thiết kế.
        - Không từ chối vì có tên sinh viên, mã sinh viên, điểm số hoặc dữ liệu minh họa trong giao diện phần mềm demo.
        - Không từ chối vì chưa khớp chuyên ngành hoàn toàn.

        Dữ liệu sản phẩm:
        {$json}

        Quy tắc trả lời:
        - Chỉ trả JSON hợp lệ, không markdown, không giải thích ngoài JSON.
        - approved=false chỉ khi có vi phạm rõ ràng thuộc các nhóm cần kiểm tra ở trên.
        - Nếu không chắc chắn hoặc vi phạm nhẹ, hãy approved=true.
        - violations là mảng chuỗi tiếng Việt, mô tả cụ thể vi phạm.
        - Nếu vi phạm nằm trong ảnh, bắt đầu violation bằng "Ảnh:".

        Định dạng JSON:
        {
            "approved": true,
            "score": 0-100,
            "reason": "giải thích ngắn gọn bằng tiếng Việt",
            "violations": [],
            "role": "{$role}",
            "checks": {
                "educational": true,
                "adult_or_sensitive": false,
                "violence_or_danger": false,
                "spam_or_meme": false,
                "clickbait_or_ads": false,
                "low_quality_or_unprofessional": false,
                "illegal_or_unethical": false,
                "discrimination_or_incitation": false,
                "software_ui_or_prototype": false
            }
        }
        PROMPT;
    }
}
