<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

enum AiRiskLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case UNKNOWN = 'unknown'; // AI verification was skipped

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
