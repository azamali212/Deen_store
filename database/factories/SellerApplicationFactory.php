<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Seller\Enums\BusinessType;
use App\Domain\Seller\Enums\SellerApplicationStatus;
use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerApplication>
 */
final class SellerApplicationFactory extends Factory
{
    protected $model = SellerApplication::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'store_name' => fake()->unique()->bothify('Store ####??'),
            'business_name' => fake()->company(),
            'business_type' => fake()->randomElement(BusinessType::cases()),
            'status' => SellerApplicationStatus::DRAFT,
            'rejection_reason' => null,
            'submitted_at' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SellerApplicationStatus::PENDING,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SellerApplicationStatus::APPROVED,
            'submitted_at' => now()->subDay(),
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'CNIC image is blurry.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SellerApplicationStatus::REJECTED,
            'rejection_reason' => $reason,
            'submitted_at' => now()->subDay(),
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }
}
