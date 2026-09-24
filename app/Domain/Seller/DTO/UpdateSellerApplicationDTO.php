<?php

declare(strict_types=1);

namespace App\Domain\Seller\DTO;

use App\Domain\Seller\Enums\BusinessType;
use App\Support\Concerns\HasDtoHelpers;

/**
 * PATCH — every field optional; null means "leave unchanged".
 */
final readonly class UpdateSellerApplicationDTO
{
    use HasDtoHelpers;

    public function __construct(
        public ?string $storeName,
        public ?string $businessName,
        public ?BusinessType $businessType,
    ) {}

    public static function fromArray(array $data): self
    {
        $businessType = self::nullableString($data, 'business_type');

        return new self(
            storeName: self::nullableString($data, 'store_name'),
            businessName: self::nullableString($data, 'business_name'),
            businessType: $businessType !== null ? BusinessType::from($businessType) : null,
        );
    }

    /**
     * Only the fields that were actually sent, mapped to column names.
     *
     * @return array<string, string>
     */
    public function toAttributes(): array
    {
        return array_filter([
            'store_name' => $this->storeName,
            'business_name' => $this->businessName,
            'business_type' => $this->businessType?->value,
        ], static fn (?string $value): bool => $value !== null);
    }
}
