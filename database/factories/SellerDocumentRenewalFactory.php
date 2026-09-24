<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Seller\Enums\DocumentAiStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Enums\SellerRenewalStatus;
use App\Models\SellerDocumentRenewal;
use App\Models\SellerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerDocumentRenewal>
 */
final class SellerDocumentRenewalFactory extends Factory
{
    protected $model = SellerDocumentRenewal::class;

    public function definition(): array
    {
        return [
            'seller_profile_id' => SellerProfile::factory(),
            'document_type' => SellerDocumentType::CNIC_FRONT->value,
            'file_path' => 'seller-renewals/1/'.$this->faker->uuid().'.pdf',
            'original_name' => 'cnic-front.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 120_000,
            'ai_status' => DocumentAiStatus::SKIPPED->value,
            'ai_findings' => null,
            'ai_checked_at' => null,
            'extracted_expiry_date' => null,
            'status' => SellerRenewalStatus::PENDING->value,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => SellerRenewalStatus::APPROVED->value,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'The scan is unreadable.'): static
    {
        return $this->state(fn (): array => [
            'status' => SellerRenewalStatus::REJECTED->value,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
