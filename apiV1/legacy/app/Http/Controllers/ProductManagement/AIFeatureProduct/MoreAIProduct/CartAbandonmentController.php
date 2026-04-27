<?php

namespace App\Http\Controllers\ProductManagement\AIFeatureProduct\MoreAIProduct;

use App\Http\Controllers\Controller;
use App\Repositories\ProductManagement\AIFeatureProductRepo\CartProductRepo\CartAbandonmentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartAbandonmentController extends Controller
{
    protected CartAbandonmentRepositoryInterface $cartAbandonmentRepository;

    public function __construct(CartAbandonmentRepositoryInterface $cartAbandonmentRepository)
    {
        $this->cartAbandonmentRepository = $cartAbandonmentRepository;
    }

    public function trackCart(string $userId): JsonResponse
    {
        $cartItems = $this->cartAbandonmentRepository->trackAbandonedCart($userId);

        return response()->json([
            'success' => true,
            'data' => $cartItems,
        ], 200);
    }
}