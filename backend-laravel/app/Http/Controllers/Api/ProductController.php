<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductViewRequest;
use App\Services\ProductService;
use Illuminate\Http\Request;

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
