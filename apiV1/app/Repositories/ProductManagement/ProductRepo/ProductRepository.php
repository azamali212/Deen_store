<?php

namespace App\Repositories\ProductManagement\ProductRepo;

use App\Models\Product;

class ProductRepository implements ProductRepositoryInterface
{
    public function getAllProducts(int $perPage = 10)
{
    return Product::with(['category', 'subcategory', 'brand', 'variants', 'images'])->paginate($perPage);
}

public function getProduct(int $id)
{
    return Product::with(['category', 'subcategory', 'brand', 'variants', 'images'])->findOrFail($id);
}


    public function createProduct(array $data)
    {
        return Product::create($data);
    }
    public function updateProduct(array $data, int $id)
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product;
    }

    public function deleteProduct(int $id)
    {
        return Product::destroy($id);
    }

    public function filterProducts(array $filters)
    {
        $query = Product::query();

        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }
        if (!empty($filters['brand'])) {
            $query->where('brand_id', $filters['brand']);
        }
        if (!empty($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }
        if (!empty($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }
        if (!empty($filters['discount'])) {
            $query->where('discount_price', '>=', $filters['discount']);
        }
        if (!empty($filters['stock'])) {
            $query->where('stock_quantity', '>=', $filters['stock']);
        }
        if (!empty($filters['status'])) {
            $query->where('is_active', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        return $query->get();
    }
    public function getSortedAndPaginatedProducts(array $filters, string $sortBy = 'created_at', string $sortDirection = 'desc', int $perPage = 10)
    {
        $query = Product::query();

        // Apply filters
        foreach ($filters as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }

        // Apply sorting and pagination
        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }
}