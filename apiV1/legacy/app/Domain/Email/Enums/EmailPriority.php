<?php

declare(strict_types=1);

namespace App\Domain\Email\Enums;

enum EmailPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isLow(): bool
    {
        return $this === self::LOW;
    }

    public function isNormal(): bool
    {
        return $this === self::NORMAL;
    }

    public function isHigh(): bool
    {
        return $this === self::HIGH;
    }

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::NORMAL => 'Normal',
            self::HIGH => 'High',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::NORMAL => 5,
            self::HIGH => 10,
        };
    }
}