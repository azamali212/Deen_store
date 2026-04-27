<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'location' => fake()->city(), // City name
            'last_known_location' => fake()->city() . ', ' . fake()->country(), // Full location string
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'last_location_updated_at' => now()->subDays(rand(0, 30)),
            'last_login_at' => now()->subDays(rand(0, 30)),
            'last_login_ip' => fake()->ipv4(),
            'status' => fake()->randomElement(['active', 'inactive']), // Add status
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
