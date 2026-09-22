<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Enums;

enum ModerationSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public function priority(): int
    {
        return match ($this) {
            self::LOW => 10,
            self::MEDIUM => 20,
            self::HIGH => 30,
        };
    }
}
