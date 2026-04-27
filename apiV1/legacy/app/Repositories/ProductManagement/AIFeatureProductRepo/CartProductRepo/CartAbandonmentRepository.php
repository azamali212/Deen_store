<?php

namespace App\Repositories\ProductManagement\AIFeatureProductRepo\CartProductRepo;

use App\Models\Cart;

class CartAbandonmentRepository implements CartAbandonmentRepositoryInterface
{

    public function trackAbandonedCart(string $userId)
    {
        //Temporary Method
        $cartItems = Cart::where('user_id', $userId)
        ->with('cartItems.product') // Ensure relationship is loaded
        ->get()
        ->pluck('cartItems')
        ->flatten()
        ->pluck('product')
        ->unique()
        ->values()
        ->toArray();

        //This Method UnComment When i Create Cart And CartItem Crud
        //$cartItems = Cart::where('user_id', $userId)
            // ->where('updated_at', '<', now()->subHours(24))
            // ->with('cartItems.product')
            // ->get()
            // ->pluck('cartItems')
            // ->flatten()
            // ->pluck('product')
            // ->unique()
            // ->values()
            // ->toArray();

        // Log the cart items
      //  \Log::info('Cart Items:', $cartItems);

        // Return the formatted cart items
        return $cartItems;
    }
}
