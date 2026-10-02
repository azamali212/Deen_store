<?php

declare(strict_types=1);

namespace App\Domain\Seller\DTO;

use App\Domain\Seller\Enums\BusinessType;
use App\Support\Concerns\HasDtoHelpers;

final readonly class CreateSellerApplicationDTO
{
    use HasDtoHelpers;

    public function __construct(
        public string $storeName,
        public string $businessName,
        public BusinessType $businessType,
        public string $country,
        public bool $acceptedDocumentProcessing = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            storeName: self::requiredString($data, 'store_name'),
            businessName: self::requiredString($data, 'business_name'),
            businessType: BusinessType::from(self::requiredString($data, 'business_type')),
            // P10-1 — decides which documents are asked for.
            country: strtoupper(self::requiredString($data, 'country')),
            acceptedDocumentProcessing: self::boolean($data, 'accept_document_processing', false),
        );
    }
}
