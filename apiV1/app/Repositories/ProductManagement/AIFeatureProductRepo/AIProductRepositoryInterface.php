<?php 

namespace App\Repositories\ProductManagement\AIFeatureProductRepo;

interface AIProductRepositoryInterface{
    public function getRecommendedProducts(string $userid);
    public function getRecommendedCategory(string $userid);

    public function getTrendingProducts();
    public function trackCategoryView(string $userId, int $productId);

    public function trackProductView(string $userId, int $productId);
}