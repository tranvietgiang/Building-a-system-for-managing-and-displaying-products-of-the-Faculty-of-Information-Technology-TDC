<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductViewRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettingService;

class ProductController extends Controller
{

    public function __construct(
        protected ProductService $productService,
        protected SystemSettingService $settings
    ) {}

    public function productViewId(int $id)
    {
        $result =  $this->productService->getProductDetailById($id);

        if (!$result) {
            return response()->json([
                'message' => 'KhÃ´ng tÃ¬m tháº¥y sáº£n pháº©m cáº§n tÃ¬m!',
                'product_result' => false,
            ], 404);
        }

        return response()->json(
            $result
        );
    }

    public function productAll(Request $request)
    {
        $request->validate([
            'status' => ['nullable', 'in:approved,pending,rejected'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min($perPage, 100));
        $status = $request->query('status');

        $result = $this->productService->getAllProductsByUserId($perPage, $status);

        return response()->json([
            'data' => $result,
            'stats' => $this->productService->getCurrentUserProductCounts(),
        ]);
    }

    public function searchProducts(Request $request)
    {
        if (!$this->settings->enabled(SystemSettingService::PRODUCT_SEARCH)) {
            return response()->json([
                'message' => 'TÃ­nh nÄƒng tÃ¬m kiáº¿m sáº£n pháº©m hiá»‡n Ä‘ang bá»‹ quáº£n trá»‹ viÃªn táº¯t.',
                'count' => 0,
                'products' => [],
                'data' => [
                    'data' => [],
                    'current_page' => 1,
                    'from' => 0,
                    'last_page' => 1,
                    'per_page' => (int) $request->query('per_page', 12),
                    'to' => 0,
                    'total' => 0,
                ],
            ], 503);
        }

        $request->validate([
            'q' => ['nullable', 'string', 'max:300'],
            'status' => ['nullable', 'in:approved,pending,rejected'],
            'major_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'in:newest,most_viewed,most_liked'],
        ], [
            'q.string' => 'Ná»™i dung tÃ¬m kiáº¿m khÃ´ng há»£p lá»‡.',
            'q.max' => 'Ná»™i dung tÃ¬m kiáº¿m khÃ´ng Ä‘Æ°á»£c vÆ°á»£t quÃ¡ 300 kÃ½ tá»±.',
            'status.in' => 'Tráº¡ng thÃ¡i tÃ¬m kiáº¿m khÃ´ng há»£p lá»‡.',
            'major_id.integer' => 'NgÃ nh há»c khÃ´ng há»£p lá»‡.',
            'per_page.integer' => 'Sá»‘ lÆ°á»£ng sáº£n pháº©m má»—i trang khÃ´ng há»£p lá»‡.',
            'per_page.min' => 'Sá»‘ lÆ°á»£ng sáº£n pháº©m má»—i trang pháº£i lá»›n hÆ¡n 0.',
            'per_page.max' => 'Sá»‘ lÆ°á»£ng sáº£n pháº©m má»—i trang khÃ´ng Ä‘Æ°á»£c vÆ°á»£t quÃ¡ 100.',
        ]);

        $keyword = trim((string) $request->query('q', ''));

        if ($this->containsUnsafeInput($keyword)) {
            return response()->json([
                'message' => 'Ná»™i dung tÃ¬m kiáº¿m chá»©a kÃ½ tá»± khÃ´ng há»£p lá»‡.',
                'errors' => [
                    'q' => ['Ná»™i dung tÃ¬m kiáº¿m chá»©a kÃ½ tá»± khÃ´ng há»£p lá»‡.'],
                ],
            ], 422);
        }

        $perPage = (int) $request->query('per_page', 12);
        $perPage = max(1, min($perPage, 100));
        $user = Auth::guard('sanctum')->user() ?? $request->user();
        $role = $user->role ?? 'guest';
        $majorId = $request->query('major_id');
        $status = $request->query('status');
        $detectedMajorCode = $this->detectMajorCodeFromKeyword($keyword);
        $searchKeyword = $detectedMajorCode
            ? $this->cleanKeywordForMajor($keyword, $detectedMajorCode)
            : $keyword;

        $query = $this->baseProductSearchQuery();
        $scoutIds = [];

        if ($searchKeyword !== '') {
            $scoutIds = Product::search($searchKeyword)->keys()->map(fn($id) => (int) $id)->all();

            if (empty($scoutIds)) {
                if ($detectedMajorCode) {
                    $searchKeyword = '';
                } else {
                    return response()->json([
                        'message' => 'TÃ¬m kiáº¿m thÆ°á»ng thÃ nh cÃ´ng.',
                        'query' => $keyword,
                        'search_keyword' => $searchKeyword,
                        'major_code' => $detectedMajorCode,
                        'mode' => 'scout',
                        'count' => 0,
                        'products' => [],
                        'data' => [
                            'data' => [],
                            'current_page' => 1,
                            'from' => 0,
                            'last_page' => 1,
                            'per_page' => $perPage,
                            'to' => 0,
                            'total' => 0,
                        ],
                    ]);
                }
            }

            if (!empty($scoutIds)) {
                $query->whereIn('products.product_id', $scoutIds);
            }
        }

        if ($detectedMajorCode) {
            $query->whereIn(DB::raw('UPPER(majors.major_code)'), $this->majorAliases($detectedMajorCode));
        }

        if (in_array($role, ['student', 'teacher'], true) && $user?->major_id) {
            $query->where('products.major_id', $user->major_id);
        } elseif ($role !== 'admin') {
            $query->where('products.status', 'approved');
        }

        if ($role === 'admin' && $status) {
            $query->where('products.status', $status);
        }

        if ($majorId) {
            $query->where('products.major_id', $majorId);
        }

        $sortBy = $request->query('sort_by', 'newest');

        if ($searchKeyword !== '' && !empty($scoutIds)) {
            $placeholders = implode(',', array_fill(0, count($scoutIds), '?'));
            $query->orderByRaw("FIELD(products.product_id, {$placeholders})", $scoutIds);
        } elseif ($sortBy === 'most_viewed') {
            $query->orderByDesc(DB::raw('COALESCE(product_statistics.views, 0)'))
                ->orderByDesc('products.submitted_at')
                ->orderByDesc('products.created_at');
        } elseif ($sortBy === 'most_liked') {
            $query->orderByDesc(DB::raw('COALESCE(product_statistics.likes, 0)'))
                ->orderByDesc('products.submitted_at')
                ->orderByDesc('products.created_at');
        } else {
            $query->orderByDesc('products.submitted_at')
                ->orderByDesc('products.created_at');
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'message' => 'TÃ¬m kiáº¿m thÆ°á»ng thÃ nh cÃ´ng.',
            'query' => $keyword,
            'search_keyword' => $searchKeyword,
            'major_code' => $detectedMajorCode,
            'mode' => 'scout',
            'count' => $paginator->total(),
            'stats' => $this->productService->getVisitorStats(),
            'products' => $paginator->items(),
            'data' => $paginator,
        ]);
    }

    private function baseProductSearchQuery()
    {
        return $this->applyProductSearchJoins(Product::query());
    }

    private function applyProductSearchJoins($query)
    {
        return $query
            ->leftJoin('users', 'products.user_id', '=', 'users.user_id')
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
                'products.created_at',
                'users.name as student_name',
                'users.user_id as student_id',
                'majors.major_name',
                'majors.major_code',
                'categories.category_name',
                DB::raw('COALESCE(product_statistics.views, 0) as views'),
                DB::raw('COALESCE(product_statistics.likes, 0) as likes')
            );
    }

    private function detectMajorCodeFromKeyword(string $keyword): ?string
    {
        $text = $this->normalizeSearchText($keyword);

        if ($text === '') {
            return null;
        }

        $majorKeywords = [
            'GRAPHIC' => ['do hoa', 'thiet ke do hoa', 'graphic', 'graphics', 'graphic design', 'tkdh', 'poster', 'logo', 'figma', 'ui ux', 'branding'],
            'AI' => ['ai', 'tri tue nhan tao', 'artificial intelligence', 'machine learning', 'deep learning', 'hoc may'],
            'CNTT' => ['cntt', 'cong nghe thong tin', 'information technology', 'phan mem', 'lap trinh', 'web', 'mobile', 'laravel', 'react'],
            'MMT' => ['mmt', 'mang may tinh', 'computer network', 'computer networks', 'network', 'cybersecurity', 'bao mat', 'cisco'],
        ];

        foreach ($majorKeywords as $majorCode => $keywords) {
            foreach ($keywords as $needle) {
                if (preg_match('/\b' . preg_quote($needle, '/') . '\b/u', $text)) {
                    return $majorCode;
                }
            }
        }

        return null;
    }

    private function cleanKeywordForMajor(string $keyword, string $majorCode): string
    {
        $text = $this->normalizeSearchText($keyword);

        foreach ($this->majorKeywordAliases($majorCode) as $alias) {
            $text = preg_replace('/\b' . preg_quote($alias, '/') . '\b/u', ' ', $text);
        }

        $genericPhrases = [
            'tim kiem',
            'tim',
            'kiem',
            'san pham',
            'do an',
            'du an',
            'de tai',
            'nganh',
            'chuyen nganh',
            'tat ca',
            'cac',
            'nhung',
            've',
            'thuoc',
            'trong',
            'cua',
            'cho',
            'danh sach',
            'moi nhat',
            'da duyet',
        ];

        foreach ($genericPhrases as $phrase) {
            $text = preg_replace('/\b' . preg_quote($phrase, '/') . '\b/u', ' ', $text);
        }

        if (strtoupper($majorCode) === 'GRAPHIC') {
            $text = preg_replace('/\bthiet ke\b/u', ' ', $text);
        }

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function majorAliases(string $majorCode): array
    {
        return match (strtoupper($majorCode)) {
            'GRAPHIC' => ['TKDH', 'GRAPHIC', 'GRAPHICS', 'GR'],
            'CNTT' => ['CNTT', 'IT'],
            'MMT' => ['MMT', 'NETWORK'],
            'AI' => ['AI'],
            default => [strtoupper($majorCode)],
        };
    }

    private function majorKeywordAliases(string $majorCode): array
    {
        return match (strtoupper($majorCode)) {
            'GRAPHIC' => ['thiet ke do hoa', 'do hoa', 'graphic design', 'graphics', 'graphic', 'tkdh'],
            'CNTT' => ['cong nghe thong tin', 'information technology', 'phan mem', 'lap trinh', 'cntt', 'it'],
            'MMT' => ['mang may tinh', 'computer networks', 'computer network', 'network', 'cybersecurity', 'bao mat', 'mmt'],
            'AI' => ['tri tue nhan tao', 'artificial intelligence', 'machine learning', 'deep learning', 'hoc may', 'ai'],
            default => [mb_strtolower($majorCode, 'UTF-8')],
        };
    }

    private function normalizeSearchText(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = str_replace(
            ['Ã ', 'Ã¡', 'áº¡', 'áº£', 'Ã£', 'Ã¢', 'áº§', 'áº¥', 'áº­', 'áº©', 'áº«', 'Äƒ', 'áº±', 'áº¯', 'áº·', 'áº³', 'áºµ', 'Ã¨', 'Ã©', 'áº¹', 'áº»', 'áº½', 'Ãª', 'á»', 'áº¿', 'á»‡', 'á»ƒ', 'á»…', 'Ã¬', 'Ã­', 'á»‹', 'á»‰', 'Ä©', 'Ã²', 'Ã³', 'á»', 'á»', 'Ãµ', 'Ã´', 'á»“', 'á»‘', 'á»™', 'á»•', 'á»—', 'Æ¡', 'á»', 'á»›', 'á»£', 'á»Ÿ', 'á»¡', 'Ã¹', 'Ãº', 'á»¥', 'á»§', 'Å©', 'Æ°', 'á»«', 'á»©', 'á»±', 'á»­', 'á»¯', 'á»³', 'Ã½', 'á»µ', 'á»·', 'á»¹', 'Ä‘'],
            ['a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y', 'd'],
            $value
        );
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function containsUnsafeInput(string $value): bool
    {
        return (bool) preg_match('/<[^>]*>|javascript:/i', $value);
    }

    public function productViewIdTeacher(ProductViewRequest $p_rq)
    {
        $result = $this->productService->productViewIdTeacher(
            (int) $p_rq->product_id,
            $p_rq->user()
        );

        if (!$result) {
            return response()->json([
                'message' => 'KhÃ´ng tÃ¬m tháº¥y sáº£n pháº©m hoáº·c báº¡n khÃ´ng cÃ³ quyá»n xem sáº£n pháº©m nÃ y.',
                'product_result' => false,
            ], 404);
        }

        return response()->json(
            $result
        );
    }

    public function deleteProductStudent(ProductViewRequest $p_rq)
    {
        $deleted = $this->productService->deleteProductStudent((int) $p_rq->product_id);

        if (!$deleted) {
            return response()->json([
                'message' => 'Khong tim thay san pham hoac ban khong co quyen xoa san pham nay.',
                'deleted' => false,
            ], 404);
        }

        return response()->json([
            'message' => 'XÃ³a sáº£n pháº©m thÃ nh cÃ´ng',
            'deleted' => true,
        ]);
    }

    public function getProductsVisitor(Request $request)
    {
        $request->validate([
            'major_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'in:newest,most_viewed,most_liked'],
        ]);

        $perPage = (int) $request->query('per_page', 9);
        $perPage = max(1, min($perPage, 100));
        $majorId = $request->filled('major_id') ? (int) $request->query('major_id') : null;
        $sortBy = $request->query('sort_by', 'newest');

        $paginator = $this->productService->getProductsVisitor($perPage, $majorId, $sortBy);

        return response()->json([
            'message' => 'Tải danh sách sản phẩm thành công.',
            'count' => $paginator->total(),
            'products' => $paginator->items(),
            'data' => $paginator,
            'stats' => $this->productService->getVisitorStats(),
        ]);
    }

    public function getVisitorProductById($id)
    {
        $intId = (int) $id;
        $result = $this->productService->getVisitorProductById($intId);
        return response()->json(
            $result
        );
    }

    public function incrementView(int $id)
    {
        $result = $this->productService->incrementView($id);

        if (!$result) {
            return response()->json([
                'message' => 'Khong tim thay san pham.',
            ], 404);
        }

        return response()->json($result);
    }

    public function incrementLike(int $id)
    {
        $result = $this->productService->incrementLike($id);

        if (!$result) {
            return response()->json([
                'message' => 'Khong tim thay san pham.',
            ], 404);
        }

        return response()->json($result);
    }

    public function incrementShare(int $id)
    {
        $result = $this->productService->incrementShare($id);

        if (!$result) {
            return response()->json([
                'message' => 'Khong tim thay san pham.',
            ], 404);
        }

        return response()->json($result);
    }

    public function getMatchingAiProducts(int $id)
    {
        $result = $this->productService->getMatchingAiProducts($id);
        return response()->json($result);
    }
}
