<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition()
    {
        return [
            'order_id' => 1, // Creates a new Order instance
            'product_id' => Product::factory(), // Creates a new Product instance
            'product_name' => $this->faker->word, // Random product name
            'price' => $this->faker->randomFloat(2, 5, 1000), // Random price
            'discount_price' => $this->faker->randomFloat(2, 1, 500), // Random discount price
            'quantity' => $this->faker->numberBetween(1, 10), // Random quantity between 1 and 10
            'total_price' => function (array $attributes) {
                // Calculate total price based on price, discount price, and quantity
                $price = $attributes['price'];
                $discountPrice = $attributes['discount_price'];
                $quantity = $attributes['quantity'];

                return ($discountPrice ? $discountPrice : $price) * $quantity;
            },
            //'vendor_id' => 1, // Creates a new Vendor instance
        ];
    }
}
