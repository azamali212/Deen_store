<?php

namespace App\Repositories\ProductManagement\AIFeatureProductRepo\GeolocationRecommendationRepo;

interface GeolocationRecommendationRepositoryInterface
{
    public function getRecommendationsByLocation(string $userId, string $location): array;
    
    public function getRecommendedProductsForLocation(string $location): array;
    
    public function getUserLocationData(string $userId): ?array;
    
    public function saveUserLocationData(string $userId, string $location): bool;
    public function getLocationBasedCategoryId(string $location): int;
}