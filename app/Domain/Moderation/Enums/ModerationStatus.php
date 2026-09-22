<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Enums;

enum ModerationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function isResolved(): bool
    {
        return match ($this) {
            self::APPROVED,
            self::REJECTED => true,

            self::PENDING => false,
        };
    }
}
