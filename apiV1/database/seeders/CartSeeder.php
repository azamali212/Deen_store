<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

class CartSeeder extends Seeder
{
    public function run()
    {
        // Fetch some users
        $users = User::inRandomOrder()->take(5)->get(); // Get 5 random users

        foreach ($users as $user) {
            // Create a cart for each user
            $cart = Cart::create([
                'user_id' => $user->id,
                'total_price' => 0, // Will be updated
                'total_quantity' => 0, // Will be updated
            ]);

            $totalPrice = 0;
            $totalQuantity = 0;

            // Fetch some random products
            $products = Product::inRandomOrder()->take(rand(2, 5))->get(); // Each cart will have 2-5 products

            foreach ($products as $product) {
                // Get a random variant if available
                $variant = ProductVariant::where('product_id', $product->id)->inRandomOrder()->first();

                // Define price based on variant or product
                $price = $variant ? $variant->price : $product->price;
                $discountPrice = $price * (rand(80, 100) / 100); // Random discount between 80-100% of price
                $quantity = rand(1, 3); // Random quantity

                // Create a cart item
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant ? $variant->id : null,
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount_price' => $discountPrice,
                    'total_price' => $discountPrice * $quantity,
                ]);

                // Update cart totals
                $totalPrice += $discountPrice * $quantity;
                $totalQuantity += $quantity;
            }

            // Update cart totals
            $cart->update([
                'total_price' => $totalPrice,
                'total_quantity' => $totalQuantity,
            ]);
        }
    }
}