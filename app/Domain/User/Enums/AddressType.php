<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

enum AddressType: string
{
    case HOME = 'home';
    case OFFICE = 'office';
    case BILLING = 'billing';
    case SHIPPING = 'shipping';
    case WAREHOUSE = 'warehouse';
    case OTHER = 'other';

    public static function values(): array
    {
        return array_column(
            self::cases(),
            'value',
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::HOME => 'Home',
            self::OFFICE => 'Office',
            self::OTHER => 'Other',
            self::BILLING => 'Billing',
            self::SHIPPING => 'Shipping',
            self::WAREHOUSE => 'Warehouse',
        };
    }
}
