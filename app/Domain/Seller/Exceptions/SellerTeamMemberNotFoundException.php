<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 404
final class SellerTeamMemberNotFoundException extends DomainException
{
    public static function withId(int $memberId): self
    {
        return (new self('That team member was not found in your store.'))
            ->withContext(['member_id' => $memberId]);
    }

    public static function invitation(int $memberId): self
    {
        return (new self('That invitation was not found.'))
            ->withContext(['member_id' => $memberId]);
    }
}
