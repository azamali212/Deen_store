<?php

namespace App\Repositories\ProductManagement\AIFeatureProductRepo\CartProductRepo;

interface CartAbandonmentRepositoryInterface{
    public function trackAbandonedCart(string $userId);
}