<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderManager>
 */
class OrderManagerFactory extends Factory
{
    protected $model = \App\Models\OrderManager::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),  // Create a related User
            'username'     => $this->faker->unique()->userName,  // Unique username
            'phone_number' => $this->faker->phoneNumber,        // Phone number (nullable)
            'status'       => $this->faker->randomElement(['active', 'inactive', 'suspended']),  // Random status
        ];
    }
}
