<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoreManager>
 */
class StoreManagerFactory extends Factory
{
    protected $model = \App\Models\StoreManager::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username'      => $this->faker->unique()->userName,  // Unique username
            'phone_number'  => $this->faker->phoneNumber,         // Phone number (nullable)
            'profile_picture' => $this->faker->imageUrl(200, 200, 'people'), // Profile picture URL (nullable)
            'user_id'       => User::factory(),                   // Create a related User
            'status'        => $this->faker->randomElement(['active', 'inactive', 'suspended']),  // Random status
        ];
    }
}
