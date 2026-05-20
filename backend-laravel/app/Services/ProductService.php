<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\BaseRepository;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService extends BaseRepository
{
    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    public function getProductDetailById(int $productId): ?array
    {
        if (!$this->productRepository->productExists($productId)) {
            return  null;
        };

        return $this->productRepository->findProductById($productId);
    }

    public function getAllProductsByUserId(int $perPage = 50): LengthAwarePaginator
    {
        return $this->productRepository->productAllById($perPage);
    }

    public function productViewIdTeacher($productId): ?array
    {
        if (!$this->productRepository->productExists($productId)) {
            return  null;
        };

        return $this->productRepository->productViewIdTeacher($productId);
    }

    public function deleteProductStudent(int $productId): bool
    {
        if (!$this->productRepository->productExists($productId)) {
            return false;
        }

        return $this->productRepository->deleteProductStudent($productId);
    }

    public function getProductsVisitor(): array
    {
        return $this->productRepository->getProductsVisitor();
    }

    public function getVisitorProductById($productId): ?array
    {
        if (!$this->productRepository->productExists($productId)) {
            return  null;
        };

        return $this->productRepository->getVisitorProductById($productId);
    }

    public function getMatchingAiProducts($productId): array
    {
        if (!$this->productRepository->productExists($productId)) {
            return [];
        }

        return $this->productRepository->findMatchingAiProducts($productId);
    }
}
