<?php

namespace App\Repositories\ProductManagement\ProductRepo;

use App\Models\Product;
use App\Models\User;
use App\Models\UserProductView;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;

class ProductRepository implements ProductRepositoryInterface
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();  // Initialize the client here
    }

    // Get all products with pagination
    public function getAllProducts(int $perPage = 10)
    {
        return Product::with(['category', 'subcategory', 'brand', 'variants', 'images'])->paginate($perPage);
    }

    // Get a specific product
    public function getProduct(int $id)
    {
        return Product::with(['category', 'subcategory', 'brand', 'variants', 'images'])->findOrFail($id);
    }

    // Create a new product
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

    // Update an existing product
    public function updateProduct(array $data, int $id)
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product;
    }

    // Delete a product
    public function deleteProduct(int $id)
    {
        return Product::destroy($id);
    }

    // Filter products based on various criteria
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

        if (!empty($filters['price'])) {
            $query->where('price', $filters['price']);
        }

        if (!empty($filters['discount'])) {
            $query->where('discount_price', $filters['discount']);
        }

        if (!empty($filters['stock_quantity'])) {
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

    // Get products sorted and paginated
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

    // Get recommended products for a user based on their interactions
    public function getRecommendedProducts(string $userId): JsonResponse
    {
        try {
            $recommendedProducts = UserProductView::where('user_id', $userId)
                ->whereNotNull('product_id')
                ->select('product_id')
                ->groupBy('product_id')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(10)
                ->pluck('product_id');

            \Log::info('Recommended Product IDs:', $recommendedProducts->toArray());

            if ($recommendedProducts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'data' => []
                ], 404);
            }

            $products = Product::whereIn('id', $recommendedProducts)->get();

            \Log::info('Fetched Recommended Products:', $products->toArray());

            return response()->json([
                'message' => 'No product IDs found.',
                'success' => true,
                'data' => $products
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching recommendations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recommendations.',
            ], 500);
        }
    }
    // Get the most viewed category by the user and recommend products from those categories
    public function getRecommendedCategory(string $userId)
    {
        // Get most viewed category by the user
        $topCategories = UserProductView::where('user_id', $userId)
            ->selectRaw('category_id, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->pluck('category_id')
            ->toArray();

        // Get top products from these categories
        return Product::whereIn('category_id', $topCategories)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    // Get trending products based on views
    public function getTrendingProducts()
    {
        return Product::withCount('views')
            ->orderByDesc('views_count')
            ->limit(10)
            ->get();
    }
}
