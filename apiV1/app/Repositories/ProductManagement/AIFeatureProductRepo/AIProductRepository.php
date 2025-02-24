<?php

namespace App\Repositories\ProductManagement\AIFeatureProductRepo;


use App\Models\Product;
use App\Models\ProductRecommendation;
use App\Models\UserProductView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIProductRepository implements AIProductRepositoryInterface
{
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

            // Store the recommended products in the ProductRecommendation model
            $productRecommendation = ProductRecommendation::updateOrCreate(
                ['product_id' => $userId], // Assuming user_id is unique for each user
                ['recommended_product_ids' => $recommendedProducts->toArray()] // Store as JSON
            );

            \Log::info('Recommended products stored for user:', ['user_id' => $userId]);

            // Now, fetch the actual product details based on recommended IDs
            //ProcessProductRecommendations::dispatch($productRecommendation);

            // Now, fetch the actual product details based on recommended IDs
            $products = Product::whereIn('id', $recommendedProducts)->get();

            return response()->json([
                'message' => 'Recommendations fetched successfully.',
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

    public function trackCategoryView(string $userId, int $productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return null; // Let the controller handle the response
        }

        \Log::info('Tracking Category View', [
            'user_id' => $userId,
            'category_id' => $product->category_id
        ]);

        return UserProductView::create([
            'user_id' => $userId,
            'category_id' => $product->category_id
        ]);
    }

    public function trackProductView(string $userId, int $productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return null; // Let the controller handle the response
        }

        \Log::info('Tracking Product View', [
            'user_id' => $userId,
            'product_id' => $product->id  // Corrected here
        ]);

        return UserProductView::create([
            'user_id' => $userId,
            'product_id' => $product->id  // Corrected here
        ]);
    }
}
