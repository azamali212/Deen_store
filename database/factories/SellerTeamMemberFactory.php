<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Seller\Enums\SellerTeamMemberStatus;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Models\SellerProfile;
use App\Models\SellerTeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerTeamMember>
 */
final class SellerTeamMemberFactory extends Factory
{
    protected $model = SellerTeamMember::class;

    public function definition(): array
    {
        return [
            'seller_profile_id' => SellerProfile::factory(),
            'user_id' => User::factory(),
            'role' => SellerTeamRole::STAFF,
            'status' => SellerTeamMemberStatus::ACTIVE,
            'invited_by' => null,
            'invited_at' => now()->subDay(),
            'accepted_at' => now(),
            'revoked_at' => null,
        ];
    }

    public function role(SellerTeamRole $role): static
    {
        return $this->state(fn (array $attributes): array => ['role' => $role]);
    }

    public function invited(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SellerTeamMemberStatus::INVITED,
            'invited_at' => now(),
            'accepted_at' => null,
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SellerTeamMemberStatus::REVOKED,
            'revoked_at' => now(),
        ]);
    }
}
