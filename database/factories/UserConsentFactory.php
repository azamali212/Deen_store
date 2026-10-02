<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\User\Enums\ConsentType;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserConsent>
 */
final class UserConsentFactory extends Factory
{
    protected $model = UserConsent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => ConsentType::MARKETING,
            'version' => '2026-09-01',
            'granted_at' => now(),
            'withdrawn_at' => null,
            'superseded_at' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
        ];
    }

    public function ofType(ConsentType $type): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type,
        ]);
    }

    public function withdrawn(): self
    {
        return $this->state(fn (array $attributes): array => [
            'withdrawn_at' => now(),
        ]);
    }

    public function superseded(): self
    {
        return $this->state(fn (array $attributes): array => [
            'superseded_at' => now(),
        ]);
    }
}
