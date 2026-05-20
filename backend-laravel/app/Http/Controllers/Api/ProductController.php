<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\ProductViewRequest;
use Illuminate\Http\Request;
use App\Services\ProductService;
use BcMath\Number;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    //
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

    public function productAll()
    {
        $result = $this->productService->getAllProductsByUserId();
        return response()->json(
            $result
        );
    }

    public function productViewIdTeacher(ProductViewRequest $p_rq)
    {
        $result = $this->productService->productViewIdTeacher($p_rq->product_id);
        return response()->json(
            $result
        );
    }

    public function deleteProductStudent(ProductViewRequest $p_rq)
    {
        $result = $this->productService->productViewIdTeacher($p_rq->product_id);
        return response()->json(
            $result
        );
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

    public function getMatchingAiProducts(int $id)
    {
        $result = $this->productService->getMatchingAiProducts($id);
        return response()->json($result);
    }
}
