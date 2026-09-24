<?php

declare(strict_types=1);

namespace App\Domain\Seller\DTO;

use App\Support\Concerns\HasDtoHelpers;

final readonly class SuspendSellerDTO
{
    use HasDtoHelpers;

    public function __construct(
        public string $reason,
        public int $adminId,
    ) {}

    public static function fromArray(array $data, int $adminId): self
    {
        return new self(
            // The seller reads this in their email, so it is required.
            reason: self::requiredString($data, 'reason'),
            adminId: $adminId,
        );
    }
}
