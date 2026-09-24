<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\BusinessType;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Domain\Seller\Enums\SellerTeamMemberStatus;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Models\SellerApplication;
use App\Models\SellerProfile;
use App\Models\SellerTeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerProfile>
 */
final class SellerProfileFactory extends Factory
{
    protected $model = SellerProfile::class;

    public function definition(): array
    {
        // Key order matters: user_id is resolved first, so the application
        // closure below can create an approved application for the SAME user.
        return [
            'user_id' => User::factory(),
            'seller_application_id' => fn (array $attributes): int => SellerApplication::factory()
                ->approved()
                ->create(['user_id' => $attributes['user_id']])
                ->id,
            'store_name' => fake()->unique()->bothify('Shop ####??'),
            'business_name' => fake()->company(),
            'business_type' => fake()->randomElement(BusinessType::cases()),
            'logo_path' => null,
            'description' => fake()->sentence(),
            'business_address' => fake()->address(),
            'bank_account_title' => fake()->name(),
            'bank_name' => 'Meezan Bank',
            'bank_account_number' => fake()->numerify('##############'),
            'bank_account_last4' => fn (array $attributes): string => substr((string) $attributes['bank_account_number'], -4),
            'status' => SellerProfileStatus::ACTIVE,
        ];
    }

    /**
     * C22 — approval creates the owner's team row, so the factory does too.
     * Without it getForUser() (which now resolves through the team) would
     * find nothing and every seller endpoint would 404 in tests.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (SellerProfile $profile): void {
            SellerTeamMember::query()->firstOrCreate(
                [
                    'seller_profile_id' => $profile->id,
                    'user_id' => $profile->user_id,
                ],
                [
                    'role' => SellerTeamRole::OWNER->value,
                    'status' => SellerTeamMemberStatus::ACTIVE->value,
                    'accepted_at' => now(),
                ],
            );
        });
    }

    public function suspended(string $reason = 'Repeated policy violations.'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SellerProfileStatus::SUSPENDED,
            'suspension_reason' => $reason,
            'suspended_by' => User::factory(),
            'suspended_at' => now(),
        ]);
    }

    public function bankStatus(BankVerificationStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'bank_verification_status' => $status,
        ]);
    }

    // A statement was uploaded and is waiting for an admin (P6-2).
    public function bankProofPending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'bank_verification_status' => BankVerificationStatus::PENDING_REVIEW,
            'bank_proof_path' => 'seller-bank-proofs/'.fake()->uuid().'.pdf',
            'bank_proof_original_name' => 'statement.pdf',
            'bank_proof_uploaded_at' => now(),
        ]);
    }
}
