<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

enum BusinessType: string
{
    case SOLE_PROPRIETOR = 'sole_proprietor';
    case PARTNERSHIP = 'partnership';
    case COMPANY = 'company';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::SOLE_PROPRIETOR => 'Sole Proprietor',
            self::PARTNERSHIP => 'Partnership',
            self::COMPANY => 'Company',
        };
    }
}
