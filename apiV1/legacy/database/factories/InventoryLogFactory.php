<?php

namespace Database\Factories;

use App\Models\InventoryLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryLog>
 */
class InventoryLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = InventoryLog::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(),
            'type' => $this->faker->randomElement(['restock', 'sale', 'return', 'adjustment']),
            'quantity' => $this->faker->numberBetween(1, 50),
            'description' => $this->faker->sentence(),
            'order_reference' => $this->faker->uuid(),
        ];
    }
}
