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

        $query = $this->baseProductSearchQuery();
        $scoutIds = [];

        if ($keyword !== '') {
            $scoutIds = Product::search($keyword)->keys()->map(fn ($id) => (int) $id)->all();

            if (empty($scoutIds)) {
                return response()->json([
                    'message' => 'Tìm kiếm thường thành công.',
                    'query' => $keyword,
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

            $query->whereIn('products.product_id', $scoutIds);
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

        if ($keyword !== '' && !empty($scoutIds)) {
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
