<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductViewRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{

    public function __construct(
        protected ProductService $productService
    ) {}

    public function productViewId(int $id)
    {
        $result =  $this->productService->getProductDetailById($id);

        if (!$result) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm cần tìm!',
                'product_result' => false,
            ], 404);
        }

        return response()->json(
            $result
        );
    }

    public function productAll(Request $request)
    {
        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min($perPage, 100));

        $result = $this->productService->getAllProductsByUserId($perPage);
        return response()->json(
            $result
        );
    }

    public function searchProducts(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'in:approved,pending,rejected'],
            'major_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $keyword = trim((string) $request->query('q', ''));
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
                        'message' => 'Tìm kiếm thường thành công.',
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

        if ($role === 'admin' && $majorId) {
            $query->where('products.major_id', $majorId);
        }

        if ($searchKeyword !== '' && !empty($scoutIds)) {
            $placeholders = implode(',', array_fill(0, count($scoutIds), '?'));
            $query->orderByRaw("FIELD(products.product_id, {$placeholders})", $scoutIds);
        } else {
            $query->orderByDesc('products.submitted_at')
                ->orderByDesc('products.created_at');
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'message' => 'Tìm kiếm thường thành công.',
            'query' => $keyword,
            'search_keyword' => $searchKeyword,
            'major_code' => $detectedMajorCode,
            'mode' => 'scout',
            'count' => $paginator->total(),
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
            ['à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ', 'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ', 'ì', 'í', 'ị', 'ỉ', 'ĩ', 'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ', 'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ', 'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ', 'đ'],
            ['a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y', 'd'],
            $value
        );
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    public function productViewIdTeacher(ProductViewRequest $p_rq)
    {
        $result = $this->productService->productViewIdTeacher(
            (int) $p_rq->product_id,
            $p_rq->user()
        );

        if (!$result) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm hoặc bạn không có quyền xem sản phẩm này.',
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
            'message' => 'Xóa sản phẩm thành công',
            'deleted' => true,
        ]);
    }

    public function getProductsVisitor()
    {
        $result = $this->productService->getProductsVisitor();
        return response()->json(
            $result
        );
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

    public function getMatchingAiProducts(int $id)
    {
        $result = $this->productService->getMatchingAiProducts($id);
        return response()->json($result);
    }
}
