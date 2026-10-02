<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\User\Events\AccountErased;
use App\Models\SellerTeamMember;

/**
 * The other half of the erasure seam.
 *
 * The User domain must not know what a store team is, so it does not clean
 * one up. It announces that an account was erased, and the Seller domain
 * decides what that means for it: an erased account cannot remain listed as
 * somebody's manager or staff.
 *
 * Safe to run unconditionally. An OWNER can never reach this point — the
 * OpenStoreErasureBlocker refuses erasure while the store is open, and a
 * closed store's team roles were already stripped in Phase 8b.
 */
final class RemoveErasedUserFromTeamsListener
{
    public function handle(AccountErased $event): void
    {
        SellerTeamMember::query()
            ->where('user_id', $event->userId)
            ->delete();
    }
}
