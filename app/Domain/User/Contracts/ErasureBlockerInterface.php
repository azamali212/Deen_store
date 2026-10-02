<?php

declare(strict_types=1);

namespace App\Domain\User\Contracts;

use App\Models\User;

/**
 * P12-4 — "is there a reason we cannot erase this account yet?"
 *
 * The User domain must not know what a store is, what an order is, or what
 * an unpaid balance is. It only knows that other parts of the system may
 * have a legal reason to keep the account alive, and that each of them can
 * answer for itself.
 *
 * Implementations are listed in config/privacy.php. Adding one later —
 * "this user has an order that has not been delivered" — is a line of
 * config, not an edit inside this domain.
 */
interface ErasureBlockerInterface
{
    /**
     * Null means nothing here blocks erasure. A string is the reason, written
     * for the user to read, because it is what the API hands back.
     */
    public function reasonToBlock(User $user): ?string;
}
