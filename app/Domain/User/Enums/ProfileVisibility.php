<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

enum ProfileVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case FOLLOWERS = 'followers';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::PRIVATE => 'Private',
            self::FOLLOWERS => 'Followers',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::PUBLIC;
    }

    public static function values(): array
    {
        return array_column(
            self::cases(),
            'value',
        );
    }
}
