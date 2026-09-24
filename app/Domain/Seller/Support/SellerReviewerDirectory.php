<?php

declare(strict_types=1);

namespace App\Domain\Seller\Support;

use App\Domain\Permissions\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * "Who reviews sellers?" in ONE place (Q3 + D10): every super_admin and
 * platform_admin. Used by every seller notification that goes to admins.
 */
final class SellerReviewerDirectory
{
    public function all(): Collection
    {
        // whereHas instead of Spatie's User::role() scope: role() throws
        // RoleDoesNotExist if a role row is missing, and a missing admin
        // role must never make a customer's action fail.
        return User::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', [
                SystemRole::SUPER_ADMIN->value,
                SystemRole::PLATFORM_ADMIN->value,
            ]))
            ->get();
    }
}
