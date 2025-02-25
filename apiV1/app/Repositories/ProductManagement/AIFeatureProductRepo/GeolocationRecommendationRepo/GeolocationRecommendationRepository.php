<?php

namespace App\Repositories\ProductManagement\AIFeatureProductRepo\GeolocationRecommendationRepo;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class GeolocationRecommendationRepository implements GeolocationRecommendationRepositoryInterface
{
    // Define the cache time in minutes
    protected $cacheTime = 60;

    /**
     * Get product recommendations based on the user's location
     * 
     * @param string $userId
     * @param string $location
     * @return array
     */
    public function getRecommendationsByLocation(string $userId, string $location): array
    {
        try {
            // Check if recommendations are cached for the location and user
            $cacheKey = "location_recommendations_{$userId}_{$location}";
            $cachedRecommendations = Cache::get($cacheKey);

            if ($cachedRecommendations) {
                return $cachedRecommendations; // Return cached data
            }

            // Fetch recommended products based on geolocation
            $recommendedProducts = $this->getRecommendedProductsForLocation($location);

            // Cache the result for subsequent requests
            Cache::put($cacheKey, $recommendedProducts, $this->cacheTime);

            return $recommendedProducts;
        } catch (Exception $e) {
            // Log error or handle exception
            return [
                'error' => 'Failed to fetch product recommendations.',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get a list of recommended products for a given location
     * 
     * @param string $location
     * @return array
     */
    public function getRecommendedProductsForLocation(string $location): array
    {
        try {
            // Example logic: retrieve products within a specific geolocation-based category
            $products = Product::where('is_active', 1)
                ->where('category_id', $this->getLocationBasedCategoryId($location))  // Example logic to fetch based on location
                ->orderByDesc('created_at')  // Most recent products
                ->take(10)  // Limit to 10 recommendations for simplicity
                ->get();

            return $products->toArray();
        } catch (Exception $e) {
            // Log error or handle exception
            return [
                'error' => 'Failed to fetch recommended products.',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the location-based category ID
     * 
     * @param string $location
     * @return int
     */
    public function getLocationBasedCategoryId(string $location): int
    {
        // Example: You can map specific locations to categories here
        // For simplicity, assuming category 1 is linked to all locations.
        $locationCategoryMap = [
            'New York' => 2,
            'Los Angeles' => 3,
            // Add more location-category mappings here
        ];

        return $locationCategoryMap[$location] ?? 1; // Default to category 1
    }

    /**
     * Fetch the location data of a user
     * 
     * @param string $userId
     * @return array|null
     */
    public function getUserLocationData(string $userId): ?array
    {
        try {
            $user = User::find($userId);

            // Check if the user exists and has location data
            if ($user && $user->location) {
                return [
                    'user_id' => $user->id,
                    'location' => $user->location,
                ];
            }

            return null; // No location found
        } catch (Exception $e) {
            // Log error or handle exception
            return null;
        }
    }

    /**
     * Save the user's location data
     * 
     * @param string $userId
     * @param string $location
     * @return bool
     */
    public function saveUserLocationData(string $userId, string $location): bool
    {
        try {
            $user = User::find($userId);

            if ($user) {
                $user->location = $location;
                return $user->save();
            }

            return false; // User not found
        } catch (Exception $e) {
            // Log error or handle exception
            return false;
        }
    }
}
