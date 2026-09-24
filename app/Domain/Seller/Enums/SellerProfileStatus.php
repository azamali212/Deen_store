<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

enum SellerProfileStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
        };
    }
}
