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
                    'content' => 'You are an AI image moderation system. Return ONLY valid JSON. No explanation. No markdown. The reason and violations must be written in Vietnamese.',
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

            if ($response->failed()) {
                $errorBody = $response->json();

                if ($response->status() === 429) {
                    return $this->aiBusySkippedResult();
                }

                $errorMessage = $this->extractErrorMessage($errorBody);

                return [
                    'approved' => false,
                    'reason' => 'Lỗi AI: ' . $errorMessage,
                    'violations' => ['Lỗi hệ thống AI'],
                    'raw' => null,
                    'system_error' => true,
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
                            'content' => 'You are an AI image moderation system. Return ONLY valid JSON. No explanation. No markdown. The reason and violations must be written in Vietnamese.',
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
                $errorBody = $response->json();

                if ($response->status() === 429) {
                    return $this->aiBusySkippedResult();
                }

                $errorMessage = $this->extractErrorMessage($errorBody);

                return [
                    'approved' => false,
                    'reason' => 'Lỗi AI: ' . $errorMessage,
                    'violations' => ['Lỗi hệ thống AI'],
                    'raw' => null,
                    'system_error' => true,
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
        $reason = trim((string) ($result['reason'] ?? ''));

        if (!$approved && $this->isSafeEducationalSoftwareScreenshot($result, $violations, $payload)) {
            return [
                'approved' => true,
                'reason' => 'Ảnh là giao diện website, ứng dụng hoặc prototype hợp lệ, không phát hiện vi phạm rõ ràng.',
                'violations' => [],
                'raw' => $result,
            ];
        }

        if (!$approved && $this->isSafeCreativeDesignWork($result, $violations, $payload)) {
            return [
                'approved' => true,
                'reason' => 'Ảnh minh họa thiết kế hoặc sản phẩm đồ họa hợp lệ, không phát hiện vi phạm rõ ràng.',
                'violations' => [],
                'raw' => $result,
            ];
        }

        if (!$approved && count($violations) === 0 && !$this->hasStrongUnsafeSignal($reason)) {
            return [
                'approved' => true,
                'reason' => 'Ảnh không có dấu hiệu vi phạm rõ ràng nên được chấp nhận.',
                'violations' => [],
                'raw' => $result,
            ];
        }

        return [
            'approved' => $approved,
            'reason' => $reason ?: ($approved ? 'Ảnh hợp lệ' : 'Ảnh không đạt kiểm duyệt'),
            'violations' => $violations,
            'raw' => $result,
        ];
    }

    private function isSafeEducationalSoftwareScreenshot(array $result, array $violations, array $payload = []): bool
    {
        $checks = $result['checks'] ?? [];

        $content = Str::lower(implode(' ', [
            (string) ($result['reason'] ?? ''),
            implode(' ', $violations),
            (string) ($payload['title'] ?? ''),
            (string) ($payload['description'] ?? ''),
            (string) ($payload['major'] ?? ''),
        ]));

        if ($this->hasStrongUnsafeSignal($content)) {
            return false;
        }

        foreach (['adult_or_sensitive', 'violence_or_danger', 'spam_or_meme'] as $check) {
            if (($checks[$check] ?? false) === true) {
                return false;
            }
        }

        $isMarkedAsInterface =
            ($checks['software_ui_or_prototype'] ?? false) === true
            || ($checks['image_related'] ?? false) === true
            || ($checks['educational'] ?? false) === true;

        $hasInterfaceKeyword = collect([
            'website',
            'web site',
            'webpage',
            'landing page',
            'app',
            'application',
            'mobile',
            'software',
            'prototype',
            'ui',
            'ux',
            'dashboard',
            'form',
            'screen',
            'screenshot',
            'interface',
            'giao diện',
            'ứng dụng',
            'phần mềm',
            'hệ thống',
            'trang web',
            'website',
            'màn hình',
            'footer',
            'header',
            'navbar',
            'sidebar',
            'hotline',
            'contact',
            'liên hệ',
            'qr',
            'logo',
            'app store',
            'google play',
            'certificate',
            'certification',
            'chứng nhận',
            'đối tác',
            'partner',
        ])->contains(fn($term) => str_contains($content, $term));

        $onlyWeakUnrelatedWarning = collect([
            'không liên quan',
            'khong lien quan',
            'not related',
            'unrelated',
            'no connection',
            'không phù hợp chuyên ngành',
            'khong phu hop chuyen nganh',
        ])->contains(fn($term) => str_contains($content, $term));

        if ($isMarkedAsInterface && $hasInterfaceKeyword) {
            return true;
        }

        if ($this->isSoftwareOrInterfaceProject($payload) && $onlyWeakUnrelatedWarning) {
            return true;
        }

        return false;
    }

    private function isSoftwareOrInterfaceProject(array $payload): bool
    {
        $content = Str::lower(implode(' ', [
            (string) ($payload['title'] ?? ''),
            (string) ($payload['description'] ?? ''),
            (string) ($payload['major'] ?? ''),
        ]));

        return collect([
            'website',
            'web site',
            'web',
            'app',
            'application',
            'mobile',
            'software',
            'prototype',
            'ui',
            'ux',
            'dashboard',
            'form',
            'interface',
            'frontend',
            'backend',
            'react',
            'laravel',
            'php',
            'javascript',
            'database',
            'booking',
            'ecommerce',
            'e-commerce',
            'management',
            'system',
            'giao diện',
            'ứng dụng',
            'phần mềm',
            'hệ thống',
            'trang web',
            'quản lý',
            'đặt tour',
            'du lịch',
            'công nghệ thông tin',
            'cntt',
            'thiết kế',
            'đồ họa',
            'graphic',
            'design',
        ])->contains(fn($term) => str_contains($content, $term));
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
            'ui',
            'ux',
        ])->contains(fn($term) => str_contains($major, $term));

        if (!$isGraphicMajor) {
            return false;
        }

        $content = Str::lower(implode(' ', [
            (string) ($result['reason'] ?? ''),
            implode(' ', $violations),
        ]));

        return !$this->hasStrongUnsafeSignal($content);
    }

    private function hasStrongUnsafeSignal(string $content): bool
    {
        $content = Str::lower(" {$content} ");

        return collect([
            '18+',
            'khỏa thân',
            'khoa than',
            'nudity',
            ' nude ',
            'naked',
            'bộ phận sinh dục',
            'bo phan sinh duc',
            'lộ ngực',
            'lo nguc',
            'ngực trần',
            'nguc tran',
            'tình dục',
            'tinh duc',
            'sexual',
            'sex',
            'porn',
            'khiêu dâm',
            'khieu dam',
            'đồ lót gợi dục',
            'lingerie',
            'bikini gợi dục',
            'bao lực',
            'bạo lực',
            'violence',
            'gore',
            'máu me',
            'mau me',
            'blood',
            'thi thể',
            'thi the',
            'chặt đầu',
            'chat dau',
            'vũ khí',
            'vu khi',
            'weapon',
            'gun',
            'knife',
            'ma túy',
            'ma tuy',
            'drug',
            'tự sát',
            'tu sat',
            'suicide',
            'hate',
            'hateful',
            'phân biệt đối xử',
            'phan biet doi xu',
            'illegal',
            'bất hợp pháp',
            'bat hop phap',
        ])->contains(fn($term) => str_contains($content, $term));
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

    private function extractErrorMessage(array $errorBody): string
    {
        if (isset($errorBody['error']['message'])) {
            $msg = $errorBody['error']['message'];

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

            return $msg;
        }

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

    private function aiBusySkippedResult(): array
    {
        return [
            'approved' => true,
            'reason' => 'Hệ thống AI đang quá tải do kiểm duyệt nhiều ảnh liên tiếp. Ảnh được tạm thời cho qua và không bị xem là vi phạm.',
            'violations' => [],
            'raw' => null,
            'skipped' => true,
            'system_error' => true,
            'retryable' => true,
        ];
    }

    private function buildPrompt(array $payload, string $role = 'student'): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
        You are an AI image moderation system for a student scientific research product platform.

        User role: {$role}

        The role is only used as context. This moderation result is mainly used to display upload feedback in the system for Vietnamese users and students.

        Your tasks:
        - Check whether the image is related to the submitted student project, research topic, major, product, or product interface.
        - Detect adult, nudity, sexual, or pornographic content.
        - Detect violence, gore, blood, weapons, dangerous, or harmful content.
        - Detect offensive, discriminatory, illegal, hateful, or harmful content.
        - Detect spam, meme images, random personal photos, low-quality unrelated images, or content not suitable for an academic platform.
        - Detect whether the image is a valid website, mobile app, software, system, prototype, UI/UX, dashboard, form, landing page, or graphic design screenshot.

        Product data:
        {$json}

        IMPORTANT UI / WEBSITE / APP SCREENSHOT RULES:
        For student website, mobile app, software, UI/UX, graphic design, or system interface projects:

        - Screenshots of website/application sections are allowed.
        - Header, footer, navbar, sidebar, contact information, hotline, QR code, social icons, partner logos, app store badges, certificates, and company information are allowed if they appear as part of the product interface.
        - Do not reject an image only because it contains a footer, header, logo, phone number, QR code, address, or certification badge.
        - Do not mark UI sections as unrelated if they are part of a website/app screenshot.
        - Only mark an image as unrelated when it is clearly a random photo, meme, personal image, advertisement unrelated to the submitted product, or content that has no connection to the product interface/design.
        - If the image is a screenshot of a website/app interface, including header, footer, contact section, QR code, hotline, partner logos, or app store badges, approve it unless it contains unsafe or clearly unrelated content.
        - For valid website/app/software interface screenshots, set:
        "software_ui_or_prototype": true,
        "image_related": true,
        "educational": true,
        "major_match": true

        IMPORTANT MODERATION RULES:
        - Student learning, research, academic work, software projects, UI/UX projects, prototype screens, dashboards, forms, landing pages, and design projects are valid academic content.
        - Return ONLY valid JSON.
        - Do not include any explanation outside JSON.
        - The "reason" field must be written in Vietnamese for displaying moderation feedback in the system.
        - The "violations" field must be an array of specific Vietnamese strings for showing clear upload error messages to students.
        - If the violation is found in the image, each item in "violations" must start with "Ảnh:" and clearly describe the violation.
        - Do not use vague labels such as "adult_or_sensitive", "image_related", "unsafe", "not_related", or "api_error" as violation messages.
        - Do not reject only because the image contains a small logo, watermark, UI brand name, sample company name, phone number, address, QR code, partner logo, social icon, app store badge, Google Play badge, or contact information.
        - Do not reject a footer, header, navbar, sidebar, or contact section if it is part of a website/app/software interface.
        - Only reject when there is strong evidence of adult content, sexual content, violence, offensive content, illegal content, spam, meme content, or the image is completely unrelated to the submitted product.
        - If there is not enough evidence to confirm a violation, approve the image and explain the uncertainty in the "reason" field.
        - If the image looks like a real product screen, UI screen, website section, app section, or design mockup, approve it unless it contains clear unsafe content.

        JSON response format:
        {
            "approved": true,
            "score": 0,
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

        Score meaning:
        - 0-20: safe / acceptable
        - 21-50: minor concern but still acceptable if educational or related to the project
        - 51-80: risky, should be rejected only if the violation is clear
        - 81-100: serious violation, must be rejected

        Reject with approved=false only if:
        - The image contains adult, nudity, sexual, or pornographic content.
        - The image contains violence, gore, blood, weapons, or dangerous content.
        - The image contains offensive, discriminatory, illegal, hateful, or harmful content.
        - The image is spam, a meme, a random personal photo, or completely unrelated to the submitted student project.
        - The image is clearly an advertisement or promotional image unrelated to the submitted product interface/design.

        Example of a valid approved result for a website footer screenshot:
        {
            "approved": true,
            "score": 5,
            "reason": "Ảnh là phần footer của giao diện website, có thông tin liên hệ, logo đối tác, chứng nhận và mã QR. Nội dung phù hợp với ảnh giao diện sản phẩm.",
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
                "software_ui_or_prototype": true
            }
        }
        PROMPT;
    }
}
