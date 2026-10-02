<?php

declare(strict_types=1);

namespace App\Domain\Seller\Support;

use App\Domain\User\Contracts\ErasureBlockerInterface;
use App\Models\SellerProfile;
use App\Models\User;

/**
 * C60 — a seller who still owns a store that is not closed cannot be erased.
 *
 * A store is not personal data; it is a trading entity with obligations of
 * its own — buyers with open disputes, tax records, AML records tied to the
 * documents behind it. Erasing the owner out from under it would leave a
 * live shopfront with nobody legally behind it.
 *
 * So the two flows chain, and each one already exists: close the store
 * (Phase 8b), which starts the KYC retention clock (Phase 9a), then ask for
 * erasure. Nothing new had to be built to make this correct — the guard
 * just refuses to skip the first step.
 *
 * Note it checks OWNERSHIP, not membership. A staff member or manager owns
 * nothing; removing them from a team is not a legal event, so their erasure
 * is not blocked by a store somebody else owns.
 */
final class OpenStoreErasureBlocker implements ErasureBlockerInterface
{
    public function reasonToBlock(User $user): ?string
    {
        $profile = SellerProfile::query()
            ->where('user_id', $user->id)
            ->first();

        if ($profile === null || ! $profile->status->isOpen()) {
            return null;
        }

        return sprintf(
            'Your store "%s" is still open. Close the store first, then ask for your account to be deleted.',
            $profile->store_name,
        );
    }
}
