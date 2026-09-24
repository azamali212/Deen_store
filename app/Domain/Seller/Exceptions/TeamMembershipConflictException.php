<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 409
final class TeamMembershipConflictException extends DomainException
{
    // P7-1 — one person belongs to one store at a time.
    public static function alreadyInAStore(string $email): self
    {
        return (new self("{$email} is already part of a seller store."))
            ->withContext(['email' => $email]);
    }

    public static function alreadyInThisStore(string $email): self
    {
        return (new self("{$email} is already in your team."))
            ->withContext(['email' => $email]);
    }

    public static function invitationNotPending(): self
    {
        return new self('This invitation is no longer pending.');
    }
}
