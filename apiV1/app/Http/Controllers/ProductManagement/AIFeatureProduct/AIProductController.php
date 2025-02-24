<?php

namespace App\Http\Controllers\ProductManagement\AIFeatureProduct;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\ProductManagement\AIFeatureProductRepo\AIProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIProductController extends Controller
{
    protected $aiProductRepository;

    public function __construct(AIProductRepositoryInterface $aiProductRepository)
    {
        $this->aiProductRepository = $aiProductRepository;
    }

    /**
     * Get recommended products based on user activity.
     */
    public function getRecommendedProducts(Request $request, string $userId): JsonResponse
    {
        // Fetch recommendations (ensure these are properly processed)
        return $this->aiProductRepository->getRecommendedProducts($userId);
    }

    /**
     * Test: Directly return recommended products.
     */
   /* public function getProductRecommendations($productId)
    {
        $product = Product::findOrFail($productId);
    
        $products = Product::select('id as product_id', 'name', 'description')
            ->where('id', '!=', $productId) // Exclude the current product
            ->get()
            ->toArray();
    
        $data = [
            'product_id' => $product->id,  // Use $product here instead of $productRecommendation
            'products' => $products,
        ];
    
        // Call Flask AI service directly
        $response = Http::post('http://127.0.0.1:5000/process_recommendations', $data);
    
        if ($response->successful()) {
            return response()->json($response->json(), 200);
        } else {
            return response()->json(['error' => 'Flask AI service failed'], 500);
        }
    } */
    

    /**
     * Get recommended category for the user.
     */
    public function getRecommendedCategory(Request $request, string $userId): JsonResponse
    {
        $recommendedCategory = $this->aiProductRepository->getRecommendedCategory($userId);

        return response()->json([
            'success' => true,
            'data' => $recommendedCategory,
        ], 200);
    }

    /**
     * Get trending products.
     */
    public function getTrendingProducts(): JsonResponse
    {
        $trendingProducts = $this->aiProductRepository->getTrendingProducts();

        return response()->json([
            'success' => true,
            'data' => $trendingProducts,
        ], 200);
    }

    /**
     * Track a category view.
     */
    public function trackCategoryView(Request $request, $productId): JsonResponse
    {
        $userId = $request->user()->id; // Get user ID from request

        $result = $this->aiProductRepository->trackCategoryView($userId, $productId);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product view tracked successfully.',
            'user_id' => $userId
        ], 200);
    }

    /**
     * Track a product view.
     */
    public function trackProductView(Request $request, $productId): JsonResponse
    {
        $userId = $request->user()->id; // Get user ID from request

        $result = $this->aiProductRepository->trackProductView($userId, $productId);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product view tracked successfully.',
            'user_id' => $userId
        ], 200);
    }
}