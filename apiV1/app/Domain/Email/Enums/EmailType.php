<?php

declare(strict_types=1);

namespace App\Domain\Email\Enums;

enum EmailType: string
{
    case INTERNAL = 'internal';
    case SYSTEM = 'system';
    case MANUAL = 'manual';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isInternal(): bool
    {
        return $this === self::INTERNAL;
    }

    public function isSystem(): bool
    {
        return $this === self::SYSTEM;
    }

    public function isManual(): bool
    {
        return $this === self::MANUAL;
    }

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Internal',
            self::SYSTEM => 'System',
            self::MANUAL => 'Manual',
        };
    }

    public function isUserGenerated(): bool
    {
        return in_array($this, [self::INTERNAL, self::MANUAL], true);
    }
}