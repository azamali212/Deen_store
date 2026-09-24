<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Seller\Enums\DocumentAiStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Models\SellerApplication;
use App\Models\SellerApplicationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerApplicationDocument>
 */
final class SellerApplicationDocumentFactory extends Factory
{
    protected $model = SellerApplicationDocument::class;

    public function definition(): array
    {
        return [
            'seller_application_id' => SellerApplication::factory(),
            'document_type' => SellerDocumentType::CNIC_FRONT,
            'file_path' => 'seller-documents/'.fake()->uuid().'.pdf',
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 102400,
        ];
    }

    public function ofType(SellerDocumentType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'document_type' => $type,
            'original_name' => $type->value.'.pdf',
        ]);
    }

    /**
     * A document the AI already read (Layer 1). No concerns = passed,
     * any concern = flagged.
     *
     * @param  array<string, string|null>  $fields
     * @param  array<int, string>  $concerns
     */
    public function aiChecked(array $fields, array $concerns = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'ai_status' => $concerns === [] ? DocumentAiStatus::PASSED : DocumentAiStatus::FLAGGED,
            'ai_findings' => ['fields' => $fields, 'concerns' => $concerns],
            'ai_checked_at' => now(),
        ]);
    }

    public function aiSkipped(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ai_status' => DocumentAiStatus::SKIPPED,
            'ai_findings' => ['fields' => [], 'concerns' => []],
            'ai_checked_at' => now(),
        ]);
    }
}
