<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 422 — the invite itself does not make sense.
final class TeamInvitationNotAllowedException extends DomainException
{
    // P7-2 — only existing accounts can be invited.
    public static function noAccountForEmail(string $email): self
    {
        return (new self("No account was found for {$email}. Ask them to sign up first, then invite them."))
            ->withContext(['email' => $email]);
    }

    public static function cannotInviteYourself(): self
    {
        return new self('You are already the owner of this store.');
    }
}
