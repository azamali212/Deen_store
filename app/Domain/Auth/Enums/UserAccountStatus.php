<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

enum UserAccountStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';
    case PENDING_VERIFICATION = 'pending_verification';

    public function canLogin(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isBlocked(): bool
    {
        return in_array($this, [
            self::SUSPENDED,
            self::BANNED,
        ], true);
    }
}