<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

enum SellerTeamMemberStatus: string
{
    case INVITED = 'invited';
    case ACTIVE = 'active';
    case REVOKED = 'revoked';

    // Only an active member can actually use the store.
    public function grantsAccess(): bool
    {
        return $this === self::ACTIVE;
    }

    public function label(): string
    {
        return match ($this) {
            self::INVITED => 'Invitation pending',
            self::ACTIVE => 'Active',
            self::REVOKED => 'Removed',
        };
    }
}
