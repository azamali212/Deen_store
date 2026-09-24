<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 403 — you are in this store, but your role does not allow this action.
final class SellerTeamPermissionException extends DomainException
{
    public static function cannot(string $action): self
    {
        return (new self("Your role in this store does not allow you to {$action}."))
            ->withContext(['action' => $action]);
    }

    // C23 — the store must always keep exactly one owner.
    public static function ownCannotBeChanged(): self
    {
        return new self('You cannot change or remove your own owner access. Transfer the store first.');
    }
}
