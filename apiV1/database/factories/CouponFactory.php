<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(10)), // Generate a random uppercase coupon code
            'discount_type' => $this->faker->randomElement(['fixed', 'percentage']),
            'discount_value' => $this->faker->randomFloat(2, 5, 50), // Between 5 and 50
            'min_order_value' => $this->faker->optional()->randomFloat(2, 50, 200),
            'max_discount' => $this->faker->optional()->randomFloat(2, 20, 100),
            'usage_limit' => $this->faker->numberBetween(1, 10),
            'used_count' => 0,
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'is_active' => $this->faker->boolean(80), // 80% chance to be active
        ];
    }
}
