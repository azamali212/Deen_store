<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

enum SellerRenewalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function isReviewable(): bool
    {
        return $this === self::PENDING;
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Waiting for review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }
}
