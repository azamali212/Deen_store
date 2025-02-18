<?php

namespace App\Repositories\ProductManagement\ProductRepo;

interface ProductRepositoryInterface
{
    public function createProduct(array $data);
    public function updateProduct(array $data, int $id);
    public function deleteProduct(int $id);
    public function getProduct(int $id);
    public function getAllProducts();

    // Optimized filtering method instead of many separate filter methods
    public function filterProducts(array $filters);

    // Sorting and pagination combined into one method
    public function getSortedAndPaginatedProducts(array $filters, string $sortBy, string $sortDirection, int $perPage);

    public function getRecommendedProducts(string $userId);
    public function getRecommendedCategory(string $userId);
}
