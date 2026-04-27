<?php
namespace App\Http\Controllers\ProductManagement\AIFeatureProduct\MoreAIProduct;

use App\Http\Controllers\Controller;
use App\Repositories\ProductManagement\AIFeatureProductRepo\GeolocationRecommendationRepo\GeolocationRecommendationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeolocationRecommendationController extends Controller
{
    protected GeolocationRecommendationRepositoryInterface $geolocationRecommendationRepository;

    public function __construct(GeolocationRecommendationRepositoryInterface $geolocationRecommendationRepository)
    {
        $this->geolocationRecommendationRepository = $geolocationRecommendationRepository;
    }

    /**
     * Get product recommendations based on user's location
     * 
     * @param string $userId
     * @return JsonResponse
     */
    public function getRecommendations(string $userId): JsonResponse
    {
        // Get the user's location data
        $userLocation = $this->geolocationRecommendationRepository->getUserLocationData($userId);

        // Check if the user has a location
        if (!$userLocation || !$userLocation['location']) {
            return response()->json([
                'success' => false,
                'message' => 'User location not found.',
            ], 404);
        }

        // Get recommendations based on the user's location
        $recommendedProducts = $this->geolocationRecommendationRepository->getRecommendationsByLocation(
            $userId, 
            $userLocation['location']
        );

        // Return the recommendations as a JSON response
        return response()->json([
            'success' => true,
            'data' => $recommendedProducts,
        ], 200);
    }

    /**
     * Save the user's location
     * 
     * @param string $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function saveLocation(string $userId, Request $request): JsonResponse
    {
        $location = $request->input('location');

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Location is required.',
            ], 400);
        }

        // Save the user's location
        $saved = $this->geolocationRecommendationRepository->saveUserLocationData($userId, $location);

        if ($saved) {
            return response()->json([
                'success' => true,
                'message' => 'User location updated successfully.',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to update user location.',
        ], 500);
    }

    /**
     * Fetch product recommendations based on a specific location
     * 
     * @param string $location
     * @return JsonResponse
     */
    public function getRecommendationsByLocation(string $location): JsonResponse
    {
        $recommendedProducts = $this->geolocationRecommendationRepository->getRecommendedProductsForLocation($location);

        if (empty($recommendedProducts)) {
            return response()->json([
                'success' => false,
                'message' => 'No recommendations found for this location.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $recommendedProducts,
        ], 200);
    }

    /**
     * Fetch user location data
     * 
     * @param string $userId
     * @return JsonResponse
     */
    public function getUserLocation(string $userId): JsonResponse
    {
        $userLocation = $this->geolocationRecommendationRepository->getUserLocationData($userId);

        if (!$userLocation) {
            return response()->json([
                'success' => false,
                'message' => 'User location not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $userLocation,
        ], 200);
    }

    /**
     * Get the category ID based on a user's location
     * 
     * @param string $location
     * @return JsonResponse
     */
    public function getCategoryIdByLocation(string $location): JsonResponse
    {
        $categoryId = $this->geolocationRecommendationRepository->getLocationBasedCategoryId($location);

        return response()->json([
            'success' => true,
            'category_id' => $categoryId,
        ], 200);
    }
}