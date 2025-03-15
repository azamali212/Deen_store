<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\StoreManager;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'                 => User::factory(), // Create a related user
            'username'                => $this->faker->unique()->userName,
            'post_code'               => $this->faker->postcode,
            'phone_number'            => $this->faker->phoneNumber,
            'address'                 => $this->faker->address,
            'city'                    => $this->faker->city,
            'store_manager_id'        => StoreManager::factory(), // Create a related store manager
            'country'                 => $this->faker->country,
            'profile_picture'         => $this->faker->imageUrl(200, 200, 'people'),
            'status'                  => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'preferred_language'      => $this->faker->randomElement(['English', 'Spanish', 'French']),
            'newsletter_subscription' => $this->faker->boolean,
        ];
    }
}
