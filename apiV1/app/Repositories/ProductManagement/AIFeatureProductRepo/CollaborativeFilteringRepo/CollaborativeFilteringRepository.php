<?php 
namespace App\Repositories\ProductManagement\AIFeatureProductRepo\CollaborativeFilteringRepo;

use App\Models\Order;
use App\Models\Product;

class CollaborativeFilteringRepository implements CollaborativeFilteringRepositoryInterface
{
    public function getCollaborativeRecommendations(string $userId): array
    {
        // Assuming $userId is a string (ULID)
        $userOrders = Order::where('user_id', $userId)->pluck('product_id');

        return Product::whereIn('id', function ($query) use ($userOrders) {
            $query->select('product_id')
                ->from('orders')
                ->whereIn('user_id', function ($q) use ($userOrders) {
                    $q->select('user_id')->from('orders')->whereIn('product_id', $userOrders);
                });
        })->get()->toArray();
    }
}