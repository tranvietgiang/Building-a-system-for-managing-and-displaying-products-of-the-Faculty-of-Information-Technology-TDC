<?php

namespace App\Http\Ai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchAi
{
    public function searchAi(Request $request)
    {
        // Validate input
        $request->validate([
            'message' => 'nullable|string|max:200',
            'query' => 'nullable|string|max:200',
            'keyword' => 'nullable|string|max:200',
        ]);

        $message = trim((string) (
            $request->input('message')
            ?? $request->input('query')
            ?? $request->input('keyword')
            ?? ''
        ));

        // Check empty
        if ($message === '') {
            return response()->json([
                'message' => 'Vui lòng nhập nội dung tìm kiếm.',
                'products' => [],
                'count' => 0,
            ], 422);
        }

        // Check minimum length
        if (strlen($message) < 2) {
            return response()->json([
                'message' => 'Nội dung tìm kiếm phải ít nhất 2 ký tự.',
                'products' => [],
                'count' => 0,
            ], 422);
        }

        // Check maximum length
        if (strlen($message) > 200) {
            return response()->json([
                'message' => 'Nội dung tìm kiếm không được vượt quá 200 ký tự.',
                'products' => [],
                'count' => 0,
            ], 422);
        }

        // Sanitize input - remove dangerous characters
        if ($this->containsDangerousPatterns($message)) {
            return response()->json([
                'message' => 'Nội dung tìm kiếm chứa ký tự không hợp lệ.',
                'products' => [],
                'count' => 0,
            ], 422);
        }

        $user = $this->resolveUser($request);
        $role = $user->role ?? 'guest';
        $majorId = $user->major_id ?? null;
        $localMajorCode = $this->detectLocalMajorCode($message);
        $intent = $localMajorCode && $this->cleanKeywordForMajor($message, $localMajorCode) === ''
            ? $this->normalizeIntent([
                'keyword' => '',
                'major_code' => $localMajorCode,
                'sort' => 'relevance',
                'limit' => 12,
            ], $message)
            : $this->mergeLocalIntent($this->detectSearchIntent($message), $message);

        if ($this->isRestrictedRoleWithoutMajor($role, $majorId)) {
            return response()->json([
                'message' => 'TÃ i khoáº£n cá»§a báº¡n chÆ°a Ä‘Æ°á»£c gÃ¡n ngÃ nh há»c.',
                'query' => $message,
                'intent' => $intent,
                'count' => 0,
                'products' => [],
            ], 403);
        }

        if ($this->isDifferentMajorSearch($intent, $role, $majorId)) {
            return response()->json([
                'message' => 'Báº¡n chá»‰ cÃ³ thá»ƒ tÃ¬m kiáº¿m dá»¯ liá»‡u trong ngÃ nh cá»§a mÃ¬nh.',
                'query' => $message,
                'intent' => $intent,
                'count' => 0,
                'products' => [],
            ]);
        }

        $products = $this->searchProducts($intent, $role, $majorId);

        return response()->json([
            'message' => 'Tìm kiếm thành công.',
            'query' => $message,
            'intent' => $intent,
            'count' => $products->count(),
            'products' => $products,
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
                || str_contains($userCode, 'TKDH')
                || str_contains($userCode, 'GR');
        }

        if ($intentCode === 'CNTT') {
            return str_contains($userCode, 'CNTT') || str_contains($userCode, 'IT');
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

        if (($intent['sort'] ?? 'relevance') === 'relevance') {
            $intent['sort'] = 'relevance';
        }

        return $intent;
    }

    private function detectLocalMajorCode(string $message): ?string
    {
        $text = mb_strtolower($message, 'UTF-8');

        $majorKeywords = [
            'GRAPHIC' => ['graphic', 'graphics', 'design', 'designer', 'ui/ux', 'figma', 'poster', 'logo', 'branding', 'tkdh'],
            'AI' => ['ai', 'artificial intelligence', 'machine learning', 'deep learning', 'python'],
            'CNTT' => ['cntt', 'it', 'web', 'mobile', 'laravel', 'react', 'php'],
            'MMT' => ['mmt', 'network', 'cybersecurity', 'security', 'cisco'],
        ];

        foreach ($majorKeywords as $majorCode => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $majorCode;
                }
            }
        }

        $accentless = $this->removeVietnameseAccents($text);

        if (
            str_contains($accentless, 'do hoa')
            || str_contains($accentless, 'thiet ke do hoa')
        ) {
            return 'GRAPHIC';
        }

        if (str_contains($accentless, 'tri tue nhan tao') || str_contains($accentless, 'hoc may')) {
            return 'AI';
        }

        if (str_contains($accentless, 'cong nghe thong tin') || str_contains($accentless, 'phan mem')) {
            return 'CNTT';
        }

        if (str_contains($accentless, 'mang may tinh') || str_contains($accentless, 'bao mat')) {
            return 'MMT';
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
            'tim kiem', 'tim', 'kiem', 'cho toi', 'cho minh', 'xem', 'lay',
            'san pham', 'do an', 'du an', 'de tai', 'bai lam', 'bai tap',
            'nganh hoc', 'chuyen nganh', 'nganh', 'major', 'student',
            'products', 'product', 'projects', 'project',
            'tat ca', 'cac', 'nhung', 've', 'thuoc', 'trong', 'cua', 'cho',
            'phu hop', 'lien quan', 'danh sach', 'moi nhat', 'nhieu luot xem',
            'da duyet', 'approved',
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
                'thiet ke do hoa', 'do hoa', 'graphic design', 'graphics', 'graphic', 'tkdh',
            ],
            'CNTT' => [
                'cong nghe thong tin', 'information technology', 'phan mem',
                'lap trinh', 'cntt', 'it',
            ],
            'MMT' => [
                'mang may tinh', 'computer networks', 'computer network',
                'network', 'cybersecurity', 'bao mat', 'mmt',
            ],
            'AI' => [
                'tri tue nhan tao', 'artificial intelligence', 'machine learning',
                'deep learning', 'hoc may', 'ai',
            ],
            default => [mb_strtolower($majorCode, 'UTF-8')],
        };
    }

    private function isMajorOnlyKeyword(string $keyword, ?string $majorCode): bool
    {
        if (!$majorCode || trim($keyword) === '') {
            return false;
        }

        if ($this->detectLocalMajorCode($keyword) !== $majorCode) {
            return false;
        }

        $normalized = $this->removeVietnameseAccents(mb_strtolower($keyword, 'UTF-8'));
        $aliases = match (strtoupper($majorCode)) {
            'GRAPHIC' => ['thiet ke do hoa', 'graphic design', 'do hoa', 'graphics', 'graphic', 'tkdh'],
            'CNTT' => ['cong nghe thong tin', 'information technology', 'phan mem', 'cntt', 'it'],
            'MMT' => ['mang may tinh', 'computer networks', 'computer network', 'network', 'bao mat', 'mmt'],
            'AI' => ['tri tue nhan tao', 'artificial intelligence', 'machine learning', 'deep learning', 'hoc may', 'ai'],
            default => [mb_strtolower($majorCode, 'UTF-8')],
        };

        foreach ($aliases as $alias) {
            $normalized = str_replace($alias, ' ', $normalized);
        }

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $normalized)) === '';
    }

    private function removeVietnameseAccents(string $value): string
    {
        $from = [
            'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
            'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
            'ì', 'í', 'ị', 'ỉ', 'ĩ',
            'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
            'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
            'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
            'đ',
        ];

        $to = [
            'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
            'y', 'y', 'y', 'y', 'y',
            'd',
        ];

        return str_replace($from, $to, $value);
    }

    private function detectSearchIntent(string $message): array
    {
        $systemPrompt = <<<PROMPT
        Bạn là AI phân tích câu tìm kiếm cho hệ thống quản lý đồ án.
        Chỉ trả về JSON hợp lệ, không markdown, không giải thích.

        Schema:
        {
          "keyword": "từ khóa chính để tìm trong tiêu đề, mô tả, tag, công nghệ",
          "major_code": "AI|CNTT|MMT|GRAPHIC|null",
          "category": "tên danh mục nếu có, nếu không thì null",
          "status": "approved|pending|rejected|null",
          "sort": "relevance|newest|views|likes",
          "limit": 12
        }

        Quy đổi ngành:
        - trí tuệ nhân tạo, artificial intelligence, machine learning, học máy, deep learning => AI
        - công nghệ thông tin, phần mềm, web, mobile, lập trình => CNTT
        - mạng máy tính, network, cybersecurity, bảo mật => MMT
        - đồ họa, graphic, design, ui/ux, poster, logo => GRAPHIC
        PROMPT;

        $systemPrompt = <<<'PROMPT'
You classify Vietnamese/English product search queries for a student project gallery.
Return valid JSON only. No markdown, no explanation.

JSON schema:
{
  "keyword": "specific product/topic keyword only, or empty string",
  "major_code": "AI|CNTT|MMT|GRAPHIC|null",
  "category": "category name if explicitly mentioned, otherwise null",
  "status": "approved|pending|rejected|null",
  "sort": "relevance|newest|views|likes",
  "limit": 12
}

Major mapping:
- "do hoa", "đồ họa", "thiết kế đồ họa", "thiet ke do hoa", "graphic", "graphic design", "poster", "logo", "branding", "figma", "ui/ux" => GRAPHIC
- "tri tue nhan tao", "trí tuệ nhân tạo", "AI", "artificial intelligence", "machine learning", "hoc may", "deep learning" => AI
- "cong nghe thong tin", "công nghệ thông tin", "CNTT", "IT", "phan mem", "web", "mobile", "lap trinh" => CNTT
- "mang may tinh", "mạng máy tính", "MMT", "network", "cybersecurity", "bao mat" => MMT

Keyword rules:
- If the user only asks for a major, set keyword to "" and set major_code.
  Examples: "đồ họa", "san pham do hoa", "do an thiet ke do hoa" => keyword "", major_code "GRAPHIC".
- Remove generic words from keyword: "tim", "kiem", "san pham", "do an", "du an", "de tai", "nganh".
- Keep concrete topic/product words:
  "logo do hoa" => keyword "logo", major_code "GRAPHIC".
  "poster thiet ke do hoa" => keyword "poster", major_code "GRAPHIC".
- If the user asks "moi nhat", use sort "newest"; "xem nhieu" use "views"; "yeu thich/like" use "likes".
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/responses', [
                'model' => 'gpt-4.1-mini',
                'input' => [
                    [
                        'role' => 'system',
                        'content' => [['type' => 'input_text', 'text' => $systemPrompt]],
                    ],
                    [
                        'role' => 'user',
                        'content' => [['type' => 'input_text', 'text' => $message]],
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

        return [
            'keyword' => trim((string) ($intent['keyword'] ?? $fallbackKeyword)),
            'major_code' => $majorCode,
            'category' => $intent['category'] ?? null,
            'status' => $status,
            'sort' => $sort,
            'limit' => $limit,
        ];
    }

    private function searchProducts(array $intent, string $role, ?int $majorId)
    {
        $query = DB::table('products')
            ->leftJoin('majors', 'products.major_id', '=', 'majors.major_id')
            ->leftJoin('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
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
                'product_ai.model_used',
                'product_ai.framework as ai_framework',
                'product_ai.language as ai_language',
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

        if ($intent['keyword'] !== '') {
            $keyword = $intent['keyword'];

            $query->where(function ($subQuery) use ($keyword) {
                $like = '%' . $keyword . '%';

                $subQuery
                    ->where('products.title', 'like', $like)
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
                    ->orWhere('product_graphic.tools_used', 'like', $like)
                    ->orWhereExists(function ($tagQuery) use ($like) {
                        $tagQuery->select(DB::raw(1))
                            ->from('product_tags')
                            ->whereColumn('product_tags.product_id', 'products.product_id')
                            ->where('product_tags.tag_name', 'like', $like);
                    });
            });
        }

        match ($intent['sort']) {
            'newest' => $query->orderByDesc('products.submitted_at'),
            'views' => $query->orderByDesc('views'),
            'likes' => $query->orderByDesc('likes'),
            default => $this->orderByRelevance($query, $intent),
        };

        $products = $query->limit($intent['limit'])->get();

        if ($products->isEmpty() && $intent['keyword'] !== '') {
            $fallbackProducts = $this->fallbackSearch($intent, $role, $majorId);

            if ($fallbackProducts->isNotEmpty()) {
                return $fallbackProducts;
            }

            if ($intent['major_code']) {
                $majorOnlyIntent = array_merge($intent, ['keyword' => '']);

                return $this->searchProducts($majorOnlyIntent, $role, $majorId);
            }
        }

        return $products;
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
            $query->orderByRaw("CASE WHEN UPPER(majors.major_code) IN ({$placeholders}) THEN 0 ELSE 1 END", $aliases);
        }

        if ($intent['keyword'] !== '') {
            $keyword = $intent['keyword'];
            $query->orderByRaw(
                'CASE
                    WHEN products.title LIKE ? THEN 0
                    WHEN categories.category_name LIKE ? THEN 1
                    WHEN majors.major_name LIKE ? OR majors.major_code LIKE ? THEN 2
                    ELSE 3
                END',
                [$keyword . '%', '%' . $keyword . '%', '%' . $keyword . '%', '%' . $keyword . '%']
            );
        }

        $query->orderByDesc('views')->orderByDesc('products.submitted_at');
    }

    private function fallbackSearch(array $intent, string $role, ?int $majorId)
    {
        $query = DB::table('products')
            ->leftJoin('majors', 'products.major_id', '=', 'majors.major_id')
            ->leftJoin('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
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
                DB::raw('COALESCE(product_statistics.likes, 0) as likes')
            );

        if (in_array($role, ['student', 'teacher'], true) && $majorId) {
            $query->where('products.major_id', $majorId);
        } elseif ($role !== 'admin') {
            $query->where('products.status', 'approved');
        }

        if ($intent['major_code']) {
            $query->whereIn(DB::raw('UPPER(majors.major_code)'), $this->majorCodeAliases($intent['major_code']));
        }

        $words = collect(preg_split('/\s+/', $intent['keyword']))
            ->filter(fn($word) => mb_strlen($word) >= 2)
            ->take(5);

        if ($words->isNotEmpty()) {
            $query->where(function ($subQuery) use ($words) {
                foreach ($words as $word) {
                    $like = '%' . $word . '%';

                    $subQuery
                        ->orWhere('products.title', 'like', $like)
                        ->orWhere('products.description', 'like', $like)
                        ->orWhere('majors.major_name', 'like', $like)
                        ->orWhere('majors.major_code', 'like', $like)
                        ->orWhere('categories.category_name', 'like', $like);
                }
            });
        }

        return $query
            ->orderByDesc('views')
            ->orderByDesc('products.submitted_at')
            ->limit($intent['limit'])
            ->get();
    }

    /**
     * Check for dangerous patterns in search query
     */
    private function containsDangerousPatterns(string $message): bool
    {
        // SQL injection patterns
        $sqlPatterns = [
            '/(\b(UNION|SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|EXECUTE)\b)/i',
            '/(-{2}|\/\*|\*\/|;)/i', // SQL comments and terminators
            '/(CHAR|ASCII|SUBSTRING|LENGTH|CONCAT)/i',
        ];

        // XSS patterns
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i', // onerror=, onclick=, etc
            '/<iframe/i',
            '/<img[^>]*on/i',
            '/<svg[^>]*on/i',
        ];

        // Command injection patterns
        $commandPatterns = [
            '/[;&|`$(){}]/i',
            '/(cat|ls|rm|wget|curl|exec|system|passthru)\s+/i',
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

    /**
     * Sanitize search message
     */
    private function sanitizeSearchMessage(string $message): string
    {
        // Remove excess whitespace
        $message = preg_replace('/\s+/', ' ', trim($message));

        // Remove HTML tags
        $message = strip_tags($message);

        // Escape for database queries (though Laravel will handle this)
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return $message;
    }
}
