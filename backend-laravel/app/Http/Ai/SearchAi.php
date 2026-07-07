<?php

namespace App\Http\Ai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\SystemSettingService;

class SearchAi
{
    public function __construct(
        protected SystemSettingService $settings
    ) {}

    private function containsAny(string $value, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function searchAi(Request $request)
    {
        if (!$this->settings->enabled(SystemSettingService::AI_SEARCH)) {
            return response()->json([
                'message' => 'Tính năng tìm kiếm bằng AI hiện đang bị quản trị viên tắt.',
                'products' => [],
                'count' => 0,
            ], 503);
        }

        $request->validate(
            [
                'message' => 'nullable|string|max:200',
                'query' => 'nullable|string|max:200',
                'keyword' => 'nullable|string|max:200',
            ],
            [
                'message.string' => 'Tin nhắn không hợp lệ.',
                'message.max' => 'Tin nhắn không được vượt quá 200 ký tự.',

                'query.string' => 'Nội dung tìm kiếm không hợp lệ.',
                'query.max' => 'Nội dung tìm kiếm không được vượt quá 200 ký tự.',

                'keyword.string' => 'Từ khóa không hợp lệ.',
                'keyword.max' => 'Từ khóa không được vượt quá 200 ký tự.',
            ]
        );

        $message = trim((string) (
            $request->input('message')
            ?? $request->input('query')
            ?? $request->input('keyword')
            ?? ''
        ));

        if ($message === '') {
            return response()->json([
                'message' => 'Vui lòng nhập nội dung tìm kiếm.',
                'products' => [],
                'count' => 0,
            ], 422);
        }

        if (mb_strlen($message) < 2) {
            return response()->json([
                'message' => 'Nội dung tìm kiếm phải ít nhất 2 ký tự.',
                'products' => [],
                'count' => 0,
            ], 422);
        }

        if (mb_strlen($message) > 200) {
            return response()->json([
                'message' => 'Nội dung tìm kiếm không được vượt quá 200 ký tự.',
                'products' => [],
                'count' => 0,
            ], 422);
        }

        if ($this->containsDangerousPatterns($message)) {
            return response()->json([
                'message' => 'Nội dung tìm kiếm chứa ký tự không hợp lệ.',
                'products' => [],
                'count' => 0,
            ], 422);
        }

        // Chặn câu không phải tìm sản phẩm trước khi gọi AI / query DB
        $guardReply = $this->guardNonProductSearch($message);
        if ($guardReply !== null) {
            return response()->json([
                'message' => $guardReply,
                'query' => $message,
                'intent' => [
                    'keyword' => '',
                    'expanded_keywords' => [],
                    'major_code' => null,
                    'category' => null,
                    'status' => null,
                    'sort' => 'relevance',
                    'limit' => 12,
                    'blocked' => true,
                ],
                'count' => 0,
                'products' => [],
            ]);
        }

        $user = $this->resolveUser($request);
        $role = $user->role ?? 'guest';
        $majorId = $user->major_id ?? null;

        $localMajorCode = $this->detectLocalMajorCode($message);

        $intent = $localMajorCode && $this->cleanKeywordForMajor($message, $localMajorCode) === ''
            ? $this->normalizeIntent([
                'keyword' => '',
                'expanded_keywords' => [],
                'major_code' => $localMajorCode,
                'sort' => 'relevance',
                'limit' => 12,
            ], $message)
            : $this->mergeLocalIntent($this->detectSearchIntent($message), $message);

        if ($this->isRestrictedRoleWithoutMajor($role, $majorId)) {
            return response()->json([
                'message' => 'Tài khoản của bạn chưa được gán ngành học.',
                'query' => $message,
                'intent' => $intent,
                'count' => 0,
                'products' => [],
            ], 403);
        }

        if ($this->isDifferentMajorSearch($intent, $role, $majorId)) {
            return response()->json([
                'message' => 'Bạn chỉ có thể tìm kiếm dữ liệu trong ngành của mình.',
                'query' => $message,
                'intent' => $intent,
                'count' => 0,
                'products' => [],
            ]);
        }

        // Nếu sau khi phân tích vẫn không có dấu hiệu tìm sản phẩm thì không search bừa
        if (!$this->shouldRunProductSearch($message, $intent)) {
            return response()->json([
                'message' => 'Mình chỉ hỗ trợ tìm kiếm sản phẩm, đồ án, công nghệ, ngành hoặc danh mục liên quan trong hệ thống.',
                'query' => $message,
                'intent' => $intent,
                'count' => 0,
                'products' => [],
            ]);
        }

        $products = $this->searchProducts($intent, $role, $majorId);

        return response()->json([
            'message' => $products->isNotEmpty()
                ? 'Tìm kiếm thành công.'
                : 'Không tìm thấy sản phẩm phù hợp trong danh sách hiện có.',
            'query' => $message,
            'intent' => $intent,
            'count' => $products->count(),
            'products' => $products,
        ]);
    }

    private function guardNonProductSearch(string $message): ?string
    {
        $normalized = $this->normalizeSearchText($message);

        if ($this->isCredentialOrAdminSecretQuestion($normalized)) {
            return 'Mình không có thông tin về mật khẩu, tài khoản quản trị hoặc dữ liệu đăng nhập nội bộ.';
        }

        if ($this->isGreetingOrChatOnly($normalized)) {
            return 'Bạn có thể nhập tên sản phẩm, chủ đề, công nghệ hoặc ngành để mình tìm kiếm trong hệ thống.';
        }

        return null;
    }

    private function isCredentialOrAdminSecretQuestion(string $normalized): bool
    {
        $credentialWords = [
            'password',
            'pass',
            'mat khau',
            'mk',
            'tai khoan admin',
            'account admin',
            'admin account',
            'token',
            'secret',
            'api key',
            'apikey',
            'key',
            'dang nhap admin',
            'login admin',
        ];

        $adminWords = [
            'admin',
            'quan tri',
            'quan tri vien',
            'root',
            'super admin',
            'superadmin',
        ];

        return $this->containsAny($normalized, $credentialWords)
            && $this->containsAny($normalized, $adminWords);
    }

    private function isGreetingOrChatOnly(string $normalized): bool
    {
        $normalized = trim($normalized);

        $greetings = [
            'hi',
            'hello',
            'chao',
            'xin chao',
            'alo',
            'ok',
            'cam on',
            'thanks',
            'thank you',
        ];

        return in_array($normalized, $greetings, true);
    }

    private function shouldRunProductSearch(string $message, array $intent): bool
    {
        if (!empty($intent['major_code']) || !empty($intent['category']) || !empty($intent['status'])) {
            return true;
        }

        if (!empty($this->getSearchTerms($intent))) {
            return true;
        }

        $normalized = $this->normalizeSearchText($message);

        return $this->containsAny($normalized, [
            'san pham',
            'do an',
            'du an',
            'de tai',
            'project',
            'website',
            'web',
            'ung dung',
            'he thong',
            'quan ly',
            'java',
            'spring',
            'spring boot',
            'react',
            'reactjs',
            'mysql',
            'laravel',
            'php',
            'api',
            'figma',
            'photoshop',
            'ai',
            'machine learning',
            'mang',
            'bao mat',
            'do hoa',
            'thiet ke',
        ]);
    }

    private function resolveUser(Request $request): ?object
    {
        return Auth::guard('sanctum')->user()
            ?? $request->user()
            ?? Auth::user();
    }

    private function isRestrictedRoleWithoutMajor(string $role, mixed $majorId): bool
    {
        return in_array($role, ['student', 'teacher'], true) && !$majorId;
    }

    private function isDifferentMajorSearch(array $intent, string $role, ?int $majorId): bool
    {
        if (!in_array($role, ['student', 'teacher'], true) || !$majorId || !$intent['major_code']) {
            return false;
        }

        $userMajorCode = DB::table('majors')
            ->where('major_id', $majorId)
            ->value('major_code');

        if (!$userMajorCode) {
            return true;
        }

        return !$this->majorCodeMatches($userMajorCode, $intent['major_code']);
    }

    private function majorCodeMatches(string $userMajorCode, string $intentMajorCode): bool
    {
        $userCode = strtoupper($userMajorCode);
        $intentCode = strtoupper($intentMajorCode);

        if ($intentCode === 'GRAPHIC') {
            return str_contains($userCode, 'GRAPHIC')
                || str_contains($userCode, 'GRAPHICS')
                || str_contains($userCode, 'TKDH')
                || str_contains($userCode, 'GR');
        }

        if ($intentCode === 'CNTT') {
            return str_contains($userCode, 'CNTT') || str_contains($userCode, 'IT');
        }

        if ($intentCode === 'MMT') {
            return str_contains($userCode, 'MMT') || str_contains($userCode, 'NETWORK');
        }

        return str_contains($userCode, $intentCode);
    }

    private function mergeLocalIntent(array $intent, string $message): array
    {
        $localMajorCode = $this->detectLocalMajorCode($message);

        if (!$localMajorCode) {
            return $intent;
        }

        $intent['major_code'] = $intent['major_code'] ?: $localMajorCode;

        $intent['keyword'] = $this->cleanKeywordForMajor(
            (string) ($intent['keyword'] ?: $message),
            $intent['major_code']
        );

        return $this->normalizeIntent($intent, $message);
    }

    private function detectLocalMajorCode(string $message): ?string
    {
        $text = mb_strtolower($message, 'UTF-8');
        $accentless = $this->removeVietnameseAccents($text);

        $majorKeywords = [
            'GRAPHIC' => [
                'graphic',
                'graphics',
                'graphic design',
                'ui/ux',
                'figma',
                'photoshop',
                'illustrator',
                'poster',
                'logo',
                'banner',
                'branding',
                'tkdh',
                'do hoa',
                'thiet ke do hoa',
                'nhan dien thuong hieu',
            ],
            'AI' => [
                'artificial intelligence',
                'machine learning',
                'deep learning',
                'computer vision',
                'chatbot',
                'nlp',
                'tri tue nhan tao',
                'hoc may',
                'xu ly anh',
                'nhan dien',
                'du doan',
                'phan loai',
            ],
            'CNTT' => [
                'cntt',
                'information technology',
                'cong nghe thong tin',
                'phan mem',
                'lap trinh',
                'website',
                'web app',
                'mobile app',
                'ung dung',
                'quan ly',
                'laravel',
                'react',
                'php',
                'javascript',
                'mysql',
                'api',
            ],
            'MMT' => [
                'mmt',
                'network',
                'computer network',
                'cybersecurity',
                'security',
                'cisco',
                'packet tracer',
                'router',
                'switch',
                'firewall',
                'mang may tinh',
                'bao mat',
            ],
        ];

        foreach ($majorKeywords as $majorCode => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($accentless, $keyword) || str_contains($text, $keyword)) {
                    return $majorCode;
                }
            }
        }

        $trimmed = trim($accentless);

        if ($trimmed === 'ai') {
            return 'AI';
        }

        if ($trimmed === 'it') {
            return 'CNTT';
        }

        return null;
    }

    private function cleanKeywordForMajor(string $keyword, ?string $majorCode): string
    {
        $normalized = $this->normalizeSearchText($keyword);

        if ($normalized === '') {
            return '';
        }

        if ($majorCode) {
            foreach ($this->majorAliases($majorCode) as $alias) {
                $normalized = preg_replace('/\b' . preg_quote($alias, '/') . '\b/u', ' ', $normalized);
            }
        }

        $genericPhrases = [
            'tim kiem',
            'tim',
            'kiem',
            'search',
            'cho toi',
            'cho minh',
            'xem',
            'lay',
            'can',
            'muon',
            'san pham',
            'do an',
            'du an',
            'de tai',
            'bai lam',
            'bai tap',
            'nganh hoc',
            'chuyen nganh',
            'nganh',
            'major',
            'student',
            'students',
            'products',
            'product',
            'projects',
            'project',
            'tat ca',
            'cac',
            'nhung',
            've',
            'thuoc',
            'trong',
            'cua',
            'cho',
            'phu hop',
            'lien quan',
            'danh sach',
            'moi nhat',
            'nhieu luot xem',
            'da duyet',
            'approved',
        ];

        foreach ($genericPhrases as $phrase) {
            $normalized = preg_replace('/\b' . preg_quote($phrase, '/') . '\b/u', ' ', $normalized);
        }

        if (strtoupper((string) $majorCode) === 'GRAPHIC') {
            $normalized = preg_replace('/\bthiet ke\b/u', ' ', $normalized);
        }

        $words = collect(preg_split('/\s+/', trim($normalized)))
            ->filter(fn($word) => mb_strlen($word) >= 2)
            ->values();

        return $words->implode(' ');
    }

    private function normalizeSearchText(string $value): string
    {
        $value = $this->removeVietnameseAccents(mb_strtolower($value, 'UTF-8'));
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function majorAliases(string $majorCode): array
    {
        return match (strtoupper($majorCode)) {
            'GRAPHIC' => [
                'thiet ke do hoa',
                'do hoa',
                'graphic design',
                'graphics',
                'graphic',
                'tkdh',
            ],
            'CNTT' => [
                'cong nghe thong tin',
                'information technology',
                'phan mem',
                'lap trinh',
                'cntt',
                'it',
            ],
            'MMT' => [
                'mang may tinh',
                'computer networks',
                'computer network',
                'network',
                'cybersecurity',
                'bao mat',
                'mmt',
            ],
            'AI' => [
                'tri tue nhan tao',
                'artificial intelligence',
                'machine learning',
                'deep learning',
                'hoc may',
                'ai',
            ],
            default => [mb_strtolower($majorCode, 'UTF-8')],
        };
    }

    private function removeVietnameseAccents(string $value): string
    {
        $map = [
            'à' => 'a',
            'á' => 'a',
            'ạ' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ầ' => 'a',
            'ấ' => 'a',
            'ậ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ă' => 'a',
            'ằ' => 'a',
            'ắ' => 'a',
            'ặ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',

            'è' => 'e',
            'é' => 'e',
            'ẹ' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ê' => 'e',
            'ề' => 'e',
            'ế' => 'e',
            'ệ' => 'e',
            'ể' => 'e',
            'ễ' => 'e',

            'ì' => 'i',
            'í' => 'i',
            'ị' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',

            'ò' => 'o',
            'ó' => 'o',
            'ọ' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ồ' => 'o',
            'ố' => 'o',
            'ộ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ơ' => 'o',
            'ờ' => 'o',
            'ớ' => 'o',
            'ợ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',

            'ù' => 'u',
            'ú' => 'u',
            'ụ' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ư' => 'u',
            'ừ' => 'u',
            'ứ' => 'u',
            'ự' => 'u',
            'ử' => 'u',
            'ữ' => 'u',

            'ỳ' => 'y',
            'ý' => 'y',
            'ỵ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'đ' => 'd',
        ];

        return strtr($value, $map);
    }

    private function detectSearchIntent(string $message): array
    {
        $systemPrompt = <<<'PROMPT'
You are an AI search intent analyzer for a student project gallery database.

Your job is to convert the user's search message into structured database search intent.

Return ONLY valid JSON.
No markdown.
No explanation.

JSON schema:
{
  "keyword": "main normalized keyword or empty string",
  "expanded_keywords": ["keyword variant 1", "keyword variant 2"],
  "major_code": "AI|CNTT|MMT|GRAPHIC|null",
  "category": "category name if explicitly mentioned, otherwise null",
  "status": "approved|pending|rejected|null",
  "sort": "relevance|newest|views|likes",
  "limit": 12
}

LANGUAGE RULES:
- The prompt is written in English.
- The search result will be shown to Vietnamese users.
- keyword and expanded_keywords may contain Vietnamese, English, abbreviations, or normalized variants.
- Prefer terms that are likely to exist in the database.

DATABASE SEARCH SCOPE:
Searchable fields in the system:
- products.title
- products.description
- majors.major_name
- majors.major_code
- categories.category_name
- product_ai.model_used
- product_ai.framework
- product_ai.language
- product_ai.dataset_used
- product_cntt.programming_language
- product_cntt.framework
- product_cntt.database_used
- product_mmt.network_protocol
- product_mmt.topology_type
- product_mmt.simulation_tool
- product_graphic.design_type
- product_graphic.tools_used
- product_tags.tag_name

IMPORTANT RULES:
- Search tags when the user mentions hashtags, keywords, technologies, frameworks, tools, or topic labels.
- Tags may contain values like #Java, #MySQL, #Quản lý, #Spring Boot, React.
- Treat hashtags as normal keywords.
- This is a database search system, not a web search engine.
- Do not invent information that may not exist in the database.
- Do not generate marketing-style feature words unless the user explicitly typed them.
- Do not expand the query too broadly.
- Do not return long unrelated keyword lists.
- Keep the result close to the user's original query.

KEYWORD EXTRACTION RULES:
- Remove generic search words from keyword.
- Keep concrete topic words, product names, project names, feature names, technology names, domain names, design types, model names, framework names, database names, tools, and workflow names.
- If the user searches only by major, set keyword to empty string and set major_code.
- If the user searches a concrete topic inside a major, set both keyword and major_code when clear.
- If the major is unclear, keep major_code as null.

EXPANDED KEYWORD RULES:
expanded_keywords must only include:
- abbreviation or full-form variants directly implied by the user query
- Vietnamese and English variants of the same concept
- accent and non-accent variants if useful
- close spelling variants
- direct technology aliases
- direct database-related terms
- direct category or field-related variants

expanded_keywords must NOT include:
- broad marketing words
- random popular features
- unrelated concepts
- generic words alone
- words that are not directly connected to the user's original query
- assumptions about product features that the user did not mention

MAJOR DETECTION RULES:
Detect major_code only when the query clearly belongs to one of these groups.

AI:
- Artificial intelligence, machine learning, deep learning, computer vision, NLP, chatbot, recognition, detection, prediction, classification, model, dataset.

CNTT:
- Software, website, web app, mobile app, application, management system, programming, frontend, backend, API, database, Laravel, React, PHP, JavaScript, MySQL.

MMT:
- Computer networking, Cisco, Packet Tracer, router, switch, topology, protocol, server, firewall, cybersecurity, network security.

GRAPHIC:
- Graphic design, logo, poster, banner, branding, visual identity, packaging, UI/UX design, Figma, Photoshop, Illustrator.

SORT RULES:
- If the user asks for latest/recent/newest, set sort = "newest".
- If the user asks for most viewed/popular/views, set sort = "views".
- If the user asks for most liked/favorite/likes, set sort = "likes".
- Otherwise set sort = "relevance".

STATUS RULES:
- approved / đã duyệt / da duyet => approved
- pending / chờ duyệt / cho duyet => pending
- rejected / từ chối / tu choi => rejected
- Otherwise status = null

LIMIT RULES:
- Default limit is 12.
- Minimum 1.
- Maximum 30.

STRICT OUTPUT RULES:
- Return valid JSON only.
- Use null, not "null".
- expanded_keywords must be an array.
- expanded_keywords should normally contain 0 to 6 items.
- Do not include explanation text outside JSON.
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.text_model', 'gpt-4.1-mini'),
                'input' => [
                    [
                        'role' => 'system',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $systemPrompt,
                            ],
                        ],
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $message,
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();

                $text = data_get($result, 'output.0.content.0.text')
                    ?? data_get($result, 'output_text');

                $decoded = $this->decodeJsonIntent((string) $text);

                if (is_array($decoded)) {
                    return $this->normalizeIntent($decoded, $message);
                }
            }

            Log::warning('OpenAI search intent failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('OpenAI search intent exception', [
                'message' => $exception->getMessage(),
            ]);
        }

        return $this->normalizeIntent([], $message);
    }

    private function decodeJsonIntent(string $text): ?array
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        $decoded = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function normalizeIntent(array $intent, string $fallbackKeyword): array
    {
        $majorCode = strtoupper((string) ($intent['major_code'] ?? ''));
        $majorCode = $majorCode === 'TKDH' ? 'GRAPHIC' : $majorCode;
        $majorCode = in_array($majorCode, ['AI', 'CNTT', 'MMT', 'GRAPHIC'], true) ? $majorCode : null;

        $status = $intent['status'] ?? null;
        $status = in_array($status, ['approved', 'pending', 'rejected'], true) ? $status : null;

        $sort = $intent['sort'] ?? 'relevance';
        $sort = in_array($sort, ['relevance', 'newest', 'views', 'likes'], true) ? $sort : 'relevance';

        $limit = (int) ($intent['limit'] ?? 12);
        $limit = max(1, min($limit, 30));

        $expandedKeywords = $intent['expanded_keywords'] ?? [];

        if (!is_array($expandedKeywords)) {
            $expandedKeywords = [];
        }

        $expandedKeywords = collect($expandedKeywords)
            ->map(fn($item) => trim((string) $item))
            ->filter(fn($item) => $item !== '')
            ->unique()
            ->take(6)
            ->values()
            ->all();

        $keyword = trim((string) ($intent['keyword'] ?? $fallbackKeyword));

        return [
            'keyword' => $keyword,
            'expanded_keywords' => $expandedKeywords,
            'major_code' => $majorCode,
            'category' => $intent['category'] ?? null,
            'status' => $status,
            'sort' => $sort,
            'limit' => $limit,
        ];
    }

    private function getSearchTerms(array $intent): array
    {
        $terms = [];

        if (!empty($intent['keyword'])) {
            $terms[] = trim((string) $intent['keyword']);
        }

        foreach (($intent['expanded_keywords'] ?? []) as $keyword) {
            $keyword = trim((string) $keyword);

            if ($keyword !== '') {
                $terms[] = $keyword;
            }
        }

        $unique = [];
        $result = [];

        foreach ($terms as $term) {
            $key = $this->normalizeSearchText($term);

            if ($key === '' || isset($unique[$key])) {
                continue;
            }

            $unique[$key] = true;
            $result[] = $term;
        }

        return collect($result)
            ->filter(fn($term) => mb_strlen($term) >= 2)
            ->take(7)
            ->values()
            ->all();
    }

    private function searchProducts(array $intent, string $role, ?int $majorId)
    {
        $query = $this->baseProductQuery();

        if (in_array($role, ['student', 'teacher'], true) && $majorId) {
            $query->where('products.major_id', $majorId);
        } elseif ($role !== 'admin') {
            $query->where('products.status', 'approved');
        }

        if ($intent['major_code']) {
            $query->whereIn(DB::raw('UPPER(majors.major_code)'), $this->majorCodeAliases($intent['major_code']));
        }

        if ($intent['category']) {
            $query->where('categories.category_name', 'like', '%' . $intent['category'] . '%');
        }

        if ($intent['status']) {
            $query->where('products.status', $intent['status']);
        }

        $searchTerms = $this->getSearchTerms($intent);

        if (!empty($searchTerms)) {
            $query->where(function ($subQuery) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $like = '%' . $term . '%';

                    $subQuery
                        ->orWhere('products.title', 'like', $like)
                        ->orWhere('products.description', 'like', $like)
                        ->orWhere('majors.major_name', 'like', $like)
                        ->orWhere('majors.major_code', 'like', $like)
                        ->orWhere('categories.category_name', 'like', $like)
                        ->orWhere('product_ai.model_used', 'like', $like)
                        ->orWhere('product_ai.framework', 'like', $like)
                        ->orWhere('product_ai.language', 'like', $like)
                        ->orWhere('product_ai.dataset_used', 'like', $like)
                        ->orWhere('product_cntt.programming_language', 'like', $like)
                        ->orWhere('product_cntt.framework', 'like', $like)
                        ->orWhere('product_cntt.database_used', 'like', $like)
                        ->orWhere('product_mmt.network_protocol', 'like', $like)
                        ->orWhere('product_mmt.topology_type', 'like', $like)
                        ->orWhere('product_mmt.simulation_tool', 'like', $like)
                        ->orWhere('product_graphic.design_type', 'like', $like)
                        ->orWhere('products.github_link', 'like', $like)
                        ->orWhere('products.demo_link', 'like', $like)
                        ->orWhere('tag_summary.tags', 'like', $like)
                        ->orWhere('product_graphic.tools_used', 'like', $like);
                }
            });
        }

        match ($intent['sort']) {
            'newest' => $query->orderByDesc('products.submitted_at'),
            'views' => $query->orderByDesc('views'),
            'likes' => $query->orderByDesc('likes'),
            default => $this->orderByRelevance($query, $intent),
        };

        $products = $query->limit($intent['limit'])->get();

        if ($products->isEmpty() && !empty($searchTerms)) {
            $fallbackProducts = $this->fallbackSearch($intent, $role, $majorId);

            if ($fallbackProducts->isNotEmpty()) {
                return $fallbackProducts;
            }

            if ($intent['major_code']) {
                $majorOnlyIntent = array_merge($intent, [
                    'keyword' => '',
                    'expanded_keywords' => [],
                ]);

                return $this->searchProducts($majorOnlyIntent, $role, $majorId);
            }
        }

        return $products;
    }

    private function baseProductQuery()
    {
        $tagSummary = DB::table('product_tags')
            ->select(
                'product_id',
                DB::raw('GROUP_CONCAT(DISTINCT tag_name SEPARATOR ", ") as tags')
            )
            ->groupBy('product_id');

        return DB::table('products')
            ->leftJoin('majors', 'products.major_id', '=', 'majors.major_id')
            ->leftJoin('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
            ->leftJoinSub($tagSummary, 'tag_summary', function ($join) {
                $join->on('products.product_id', '=', 'tag_summary.product_id');
            })
            ->leftJoin('product_ai', 'products.product_id', '=', 'product_ai.product_id')
            ->leftJoin('product_cntt', 'products.product_id', '=', 'product_cntt.product_id')
            ->leftJoin('product_mmt', 'products.product_id', '=', 'product_mmt.product_id')
            ->leftJoin('product_graphic', 'products.product_id', '=', 'product_graphic.product_id')
            ->select(
                'products.product_id',
                'products.major_id',
                'products.cate_id',
                'products.title',
                'products.description',
                'products.thumbnail',
                'products.status',
                'products.github_link',
                'products.demo_link',
                'products.submitted_at',
                'majors.major_name',
                'majors.major_code',
                'categories.category_name',
                DB::raw('COALESCE(product_statistics.views, 0) as views'),
                DB::raw('COALESCE(product_statistics.likes, 0) as likes'),
                DB::raw('COALESCE(tag_summary.tags, "") as tags'),
                'product_ai.model_used',
                'product_ai.framework as ai_framework',
                'product_ai.language as ai_language',
                'product_ai.dataset_used',
                'product_ai.accuracy_score',
                'product_cntt.programming_language',
                'product_cntt.framework as cntt_framework',
                'product_cntt.database_used',
                'product_mmt.network_protocol',
                'product_mmt.topology_type',
                'product_mmt.simulation_tool',
                'product_graphic.design_type',
                'product_graphic.tools_used',
                'product_graphic.behance_link'
            );
    }

    private function majorCodeAliases(string $majorCode): array
    {
        return match (strtoupper($majorCode)) {
            'GRAPHIC' => ['TKDH', 'GRAPHIC', 'GRAPHICS', 'GR'],
            'CNTT' => ['CNTT', 'IT'],
            'MMT' => ['MMT', 'NETWORK'],
            'AI' => ['AI'],
            default => [strtoupper($majorCode)],
        };
    }

    private function orderByRelevance($query, array $intent): void
    {
        if ($intent['major_code']) {
            $aliases = $this->majorCodeAliases($intent['major_code']);
            $placeholders = implode(',', array_fill(0, count($aliases), '?'));

            $query->orderByRaw(
                "CASE WHEN UPPER(majors.major_code) IN ({$placeholders}) THEN 0 ELSE 1 END",
                $aliases
            );
        }

        $searchTerms = $this->getSearchTerms($intent);

        if (!empty($searchTerms)) {
            $scoreParts = [];
            $bindings = [];

            foreach (array_slice($searchTerms, 0, 10) as $term) {
                $like = '%' . $term . '%';

                $scoreParts[] = '
                CASE
                    WHEN products.title LIKE ? THEN 120
                    WHEN tag_summary.tags LIKE ? THEN 100
                    WHEN product_cntt.programming_language LIKE ? THEN 100
                    WHEN product_cntt.framework LIKE ? THEN 100
                    WHEN product_cntt.database_used LIKE ? THEN 100
                    WHEN product_ai.framework LIKE ? THEN 90
                    WHEN product_ai.language LIKE ? THEN 90
                    WHEN product_ai.model_used LIKE ? THEN 90
                    WHEN products.description LIKE ? THEN 70
                    WHEN categories.category_name LIKE ? THEN 45
                    WHEN products.github_link LIKE ? THEN 35
                    WHEN products.demo_link LIKE ? THEN 35
                    WHEN majors.major_name LIKE ? OR majors.major_code LIKE ? THEN 20
                    ELSE 0
                END
            ';

                array_push(
                    $bindings,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like,
                    $like
                );
            }

            if (!empty($scoreParts)) {
                $query->orderByRaw('(' . implode(' + ', $scoreParts) . ') DESC', $bindings);
            }
        }

        $query->orderByDesc('views')
            ->orderByDesc('likes')
            ->orderByDesc('products.submitted_at');
    }

    private function fallbackSearch(array $intent, string $role, ?int $majorId)
    {
        $query = $this->baseProductQuery();

        if (in_array($role, ['student', 'teacher'], true) && $majorId) {
            $query->where('products.major_id', $majorId);
        } elseif ($role !== 'admin') {
            $query->where('products.status', 'approved');
        }

        if ($intent['major_code']) {
            $query->whereIn(DB::raw('UPPER(majors.major_code)'), $this->majorCodeAliases($intent['major_code']));
        }

        $words = collect($this->getSearchTerms($intent))
            ->flatMap(fn($term) => preg_split('/\s+/', $term))
            ->map(fn($word) => trim((string) $word))
            ->filter(fn($word) => mb_strlen($word) >= 2)
            ->unique()
            ->take(10);

        if ($words->isNotEmpty()) {
            $query->where(function ($subQuery) use ($words) {
                foreach ($words as $word) {
                    $like = '%' . $word . '%';

                    $subQuery
                        ->orWhere('products.title', 'like', $like)
                        ->orWhere('products.description', 'like', $like)
                        ->orWhere('products.github_link', 'like', $like)
                        ->orWhere('products.demo_link', 'like', $like)
                        ->orWhere('majors.major_name', 'like', $like)
                        ->orWhere('majors.major_code', 'like', $like)
                        ->orWhere('categories.category_name', 'like', $like)
                        ->orWhere('tag_summary.tags', 'like', $like)
                        ->orWhere('product_ai.model_used', 'like', $like)
                        ->orWhere('product_ai.framework', 'like', $like)
                        ->orWhere('product_ai.language', 'like', $like)
                        ->orWhere('product_ai.dataset_used', 'like', $like)
                        ->orWhere('product_cntt.programming_language', 'like', $like)
                        ->orWhere('product_cntt.framework', 'like', $like)
                        ->orWhere('product_cntt.database_used', 'like', $like)
                        ->orWhere('product_mmt.network_protocol', 'like', $like)
                        ->orWhere('product_mmt.topology_type', 'like', $like)
                        ->orWhere('product_mmt.simulation_tool', 'like', $like)
                        ->orWhere('product_graphic.design_type', 'like', $like)
                        ->orWhere('product_graphic.tools_used', 'like', $like);
                }
            });
        }

        $this->orderByRelevance($query, $intent);

        return $query
            ->limit($intent['limit'])
            ->get();
    }

    private function containsDangerousPatterns(string $message): bool
    {
        $sqlPatterns = [
            '/(\b(UNION|SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|EXECUTE)\b)/i',
            '/(-{2}|\/\*|\*\/|;)/i',
            '/(CHAR|ASCII|SUBSTRING|LENGTH|CONCAT)/i',
        ];

        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<img[^>]*on/i',
            '/<svg[^>]*on/i',
        ];

        $commandPatterns = [
            '/[`$]/i',
            '/\b(cat|ls|rm|wget|curl|exec|system|passthru)\s+/i',
        ];

        $allPatterns = array_merge($sqlPatterns, $xssPatterns, $commandPatterns);

        foreach ($allPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::warning('Dangerous search pattern detected', [
                    'message' => substr($message, 0, 100),
                    'pattern' => $pattern,
                ]);

                return true;
            }
        }

        return false;
    }

    private function sanitizeSearchMessage(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', trim($message));
        $message = strip_tags($message);
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return $message;
    }
}
