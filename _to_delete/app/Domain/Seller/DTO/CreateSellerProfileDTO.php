<?php

declare(strict_types=1);

namespace App\Domain\Seller\DTO;

use App\Support\Concerns\HasDtoHelpers;

final readonly class CreateSellerProfileDTO
{
    use HasDtoHelpers;

    public function __construct(
        public int $userId,
        public string $storeName,
        public ?string $businessName,
        public ?string $businessType,
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            storeName: self::requiredString($data, 'store_name'),
            businessName: self::nullableString($data, 'business_name'),
            businessType: self::nullableString($data, 'business_type'),
        );
    }
}
