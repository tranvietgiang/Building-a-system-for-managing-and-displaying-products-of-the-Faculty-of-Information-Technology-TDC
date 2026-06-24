<?php

namespace App\Http\Ai;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Client\Response;
use App\Services\SystemSettingService;

class ContentModeration
{
    public function __construct(
        protected SystemSettingService $settings
    ) {}

    private function sendOpenAiModerationRequest(string $apiKey, array $messages): Response
    {
        $payload = [
            'model' => config('services.openai.vision_model', 'gpt-4o-mini'),
            'messages' => $messages,
            'temperature' => 0.2,
            'max_tokens' => 350,
        ];

        $response = null;

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', $payload);

            if (! $this->isOpenAiRateLimited($response)) {
                return $response;
            }

            usleep($attempt * 800_000);
        }

        return $response;
    }

    private function isOpenAiRateLimited(Response $response): bool
    {
        if ($response->status() === 429) {
            return true;
        }

        $message = (string) data_get($response->json(), 'error.message', '');

        return str_contains(Str::lower($message), 'rate limit');
    }

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

            $response = $this->sendOpenAiModerationRequest($apiKey, $messages);

            // ❌ REAL API ERROR
            if ($response->failed()) {
                $errorBody = $response->json() ?? [];
                $errorMessage = $this->extractErrorMessage($errorBody);

                Log::error('OpenAI moderation API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'product_id' => $product->product_id,
                ]);

                return [
                    'approved' => false,
                    'reason' => 'Loi AI: ' . $errorMessage,
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

            $response = $this->sendOpenAiModerationRequest($apiKey, $messages);

            if ($response->failed()) {
                $errorMessage = $this->extractErrorMessage($response->json() ?? []);

                return [
                    'approved' => false,
                    'reason' => 'Loi AI: ' . $errorMessage,
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

        foreach (
            [
                'adult_or_sensitive',
                'violence_or_danger',
                'spam_or_meme',
                'illegal_or_unethical',
                'discrimination_or_incitation',
            ] as $key
        ) {
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

            // Adult / Sexual Content
            'nudity',
            'nude',
            'sexual',
            'sex',
            'porn',
            'pornography',
            'erotic',
            'adult content',
            'explicit content',

            // Violence / Gore
            'violence',
            'violent',
            'gore',
            'blood',
            'bloody',
            'corpse',
            'dead body',
            'beheading',
            'murder',

            // Weapons / Dangerous Content
            'weapon',
            'gun',
            'firearm',
            'knife',
            'explosive',
            'danger',
            'dangerous',

            // Drugs / Self-Harm
            'drug',
            'drugs',
            'illegal drugs',
            'narcotics',
            'suicide',
            'self-harm',

            // Spam / Meme / Low Quality
            'spam',
            'meme',
            'clickbait',
            'misleading content',
            'low quality content',

            // Illegal Content
            'illegal',
            'law violation',
            'criminal activity',
            'fraud',

            // Academic Misconduct
            'academic misconduct',
            'academic dishonesty',
            'plagiarism',
            'cheating',

            // Hate / Discrimination
            'hate',
            'hate speech',
            'discrimination',
            'harassment',
            'incitement',
            'extremism',
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
            'nudity',
            'nude',
            'naked body',
            'genitals',
            'exposed breasts',
            'topless',
            'sexual content',
            'sexual',
            'pornography',
            'porn',
            'erotic content',
            'fetish lingerie',
            'lingerie',
            'bikini',

            'gore',
            'bloody scene',
            'corpse',
            'dead body',
            'beheading',
            'decapitation',

            'weapon',
            'gun',
            'knife',
            'explosive',

            'drug',
            'illegal drugs',
            'narcotics',

            'suicide',
            'self-harm',
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
            $code = (string) ($errorBody['error']['code'] ?? '');
            $lowerError = Str::lower($msg . ' ' . $code);

            // Translate common error messages to Vietnamese
            if (strpos($msg, 'invalid_image_url') !== false || strpos($msg, 'downloading') !== false) {
                return 'Lỗi ảnh: URL ảnh không hợp lệ hoặc không thể tải xuống';
            }
            if (strpos($msg, 'timeout') !== false) {
                return 'Lỗi: Yêu cầu quá thời gian chờ';
            }
            if (str_contains($lowerError, 'rate limit') || str_contains($lowerError, 'rate_limit')) {
                return 'Hệ thống AI đang quá tải do kiểm duyệt quá nhiều ảnh liên tiếp. Vui lòng thử lại sau vài giây hoặc giảm số lượng ảnh trong mỗi lần tải lên.';
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
        You are an AI moderation system responsible for reviewing student research project submissions.

        The goal of this review is to perform basic content safety checks only.

        DO NOT evaluate duplicate projects.
        DO NOT perform detailed academic quality assessment.
        DO NOT reject submissions simply because:

        * The project is simple.
        * The UI design is basic.
        * Screenshots are imperfect.
        * The project lacks advanced technical depth.

        Project similarity and duplication checks are handled separately by instructors and a dedicated comparison module.

        User Role: {$role}

        Review only the following categories:

        * Minimum suitability for an educational and research environment.
        * Adult, sexual, pornographic, or explicit content.
        * Violence, dangerous content, gore, or disturbing material.
        * Spam, meme content, obvious junk content, or extremely low-value submissions.
        * Minimum level of professionalism and seriousness.
        * Clearly illegal content or obvious academic misconduct.
        * Hate speech, discrimination, incitement, or inappropriate controversial content.

        Do NOT evaluate:

        * Whether the project is duplicated or similar to another project.
        * Watermarks, copyrights, logos, mockups, posters, interfaces, or brands used as examples in student work.
        * Software UI screenshots, prototypes, dashboards, network diagrams, posters, branding mockups, or design projects.
        * Student names, student IDs, grades, or demonstration/sample data shown inside software interfaces.
        * Whether the project perfectly matches the student's major.

        Project Data:

        {$json}

        Response Rules:

        * Return valid JSON only.
        * Do not use Markdown.
        * Do not include explanations outside the JSON response.
        * Set approved=false only when there is a clear violation within the categories listed above.
        * If uncertain or if the violation is minor, return approved=true.
        * violations must be an array of Vietnamese strings describing the specific issue.
        * If the violation is found in an image, start the violation message with "Ảnh:".

        JSON Format:

        {
        "approved": true,
        "score": 0-100,
        "reason": "Giải thích ngắn gọn bằng tiếng Việt",
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
