<?php
 namespace App\Repositories\ProductManagement\AIFeatureProductRepo\CollaborativeFilteringRepo;

 interface CollaborativeFilteringRepositoryInterface
{
    public function getCollaborativeRecommendations(string $userId): array;
}