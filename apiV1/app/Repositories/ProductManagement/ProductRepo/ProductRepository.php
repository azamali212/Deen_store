<?php

namespace App\Repositories\ProductManagement\ProductRepo;

use App\Models\Product;
use App\Models\User;

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
        \Log::info('Product Data Received:', $data); // Log the data to verify input

        $product = Product::create($data);

        if (!$product) {
            \Log::error('Product creation failed.');
            throw new \Exception('Product creation failed.');
        }
        

        \Log::info('Product Created:', $product->toArray()); // Log the created product

        return $product;
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

    public function filterProducts(array $filters, int $perPage = 10)
    {
        $query = Product::query()->with(['category', 'subcategory', 'brand', 'variants', 'images']);

        // Apply filters dynamically
        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (!empty($filters['brand'])) {
            $query->where('brand_id', $filters['brand']);
        }

        if (!empty($filters['price'])) { // Just filter by exact price
            $query->where('price', $filters['price']);
        }

        if (!empty($filters['discount'])) { // Just filter by exact discount
            $query->where('discount_price', $filters['discount']);
        }

        if (!empty($filters['stock_quantity'])) { // Just filter by exact stock quantity
            $query->where('stock_quantity', $filters['stock_quantity']);
        }

        if (!empty($filters['status'])) {
            $query->where('is_active', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        return $query->paginate($perPage);
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

    public function getRecommendedProducts(string $userId)
    {
        // Step 1: Fetch the user
        $user = User::find($userId);
        if (!$user) {
            \Log::error('User not found for ID:', ['user_id' => $userId]);
            return collect(); // User not found, return empty collection
        }

        // Step 2: Get the viewed product IDs
        $viewedProductIds = $user->viewedProducts()->pluck('product_id'); // Use pluck to get only product IDs

        \Log::info('Viewed Product IDs:', $viewedProductIds->toArray()); // Log the viewed product IDs

        if ($viewedProductIds->isEmpty()) {
            \Log::info('No viewed products for user:', ['user_id' => $userId]);
            return collect(); // No viewed products, return empty collection
        }

        // Step 3: Retrieve products based on individual product views (if any) or similar categories
        $recommendedProducts = Product::whereIn('id', $viewedProductIds)
            ->orWhereIn('category_id', $user->viewedCategories()->pluck('category_id'))
            ->limit(10)
            ->get();

        \Log::info('Recommended Products:', $recommendedProducts->toArray()); // Log the recommended products

        return $recommendedProducts;
    }
    public function getRecommendedCategory(string $userId)
    {
        // Step 1: Fetch the user
        $user = User::find($userId);
        if (!$user) {
            \Log::error('User not found for ID:', ['user_id' => $userId]);
            return collect(); // User not found, return empty collection
        }

        // Step 2: Get the viewed categories
        $viewedCategories = $user->viewedCategories()->pluck('category_id'); // Use pluck to get only category IDs

        \Log::info('Viewed Categories:', $viewedCategories->toArray()); // Log the categories

        if ($viewedCategories->isEmpty()) {
            \Log::info('No viewed categories for user:', ['user_id' => $userId]);
            return collect(); // No viewed categories, return empty collection
        }

        // Step 3: Retrieve products from those categories
        $recommendedProducts = Product::whereIn('category_id', $viewedCategories)
            ->limit(10)
            ->get();

        \Log::info('Recommended Products:', $recommendedProducts->toArray()); // Log the recommended products

        return $recommendedProducts;
    }
}
