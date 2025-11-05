<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition()
    {
        // Handle the optional delivered_at properly
        $deliveredAt = $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now');
        
        return [
            'supplier_id' => Supplier::inRandomOrder()->first()->id,
            'product_id' => Product::inRandomOrder()->first()->id,
            'quantity' => $this->faker->numberBetween(1, 100),
            'delivered_at' => $deliveredAt ? $deliveredAt->format('Y-m-d H:i:s') : null,
            'expected_delivery' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d H:i:s'),
            'rating' => $this->faker->randomFloat(2, 0, 5),
            'total_cost' => $this->faker->randomFloat(2, 50, 1000),
            'status' => $this->faker->randomElement(['pending', 'received', 'cancelled']),
            'order_number' => $this->faker->unique()->randomNumber(8),
        ];
    }
}