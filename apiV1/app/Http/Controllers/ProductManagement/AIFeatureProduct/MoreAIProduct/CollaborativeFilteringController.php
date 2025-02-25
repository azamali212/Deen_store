<?php

namespace App\Http\Controllers\ProductManagement\AIFeatureProduct\MoreAIProduct;

use App\Http\Controllers\Controller;
use App\Repositories\ProductManagement\AIFeatureProductRepo\CollaborativeFilteringRepo\CollaborativeFilteringRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollaborativeFilteringController extends Controller
{
    protected CollaborativeFilteringRepositoryInterface $collaborativeFilteringRepository;

    public function __construct(CollaborativeFilteringRepositoryInterface $collaborativeFilteringRepository)
    {
        $this->collaborativeFilteringRepository = $collaborativeFilteringRepository;
    }

    public function trackCollaborative(string $userId): JsonResponse
    {
        $trackCollaborative = $this->collaborativeFilteringRepository->getCollaborativeRecommendations($userId);

        // Check if the user has no orders or no recommendations
        if (empty($trackCollaborative)) {
            return response()->json([
                'success' => false,
                'message' => 'User has no orders, therefore no recommendations available.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $trackCollaborative,
        ], 200);
    }
}
