<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories\Queries;

use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Models\SellerProfile;
use Illuminate\Database\Eloquent\Builder;

final class SellerProfileQuery
{
    public function forUser(int $userId): Builder
    {
        return SellerProfile::query()
            ->where('user_id', $userId);
    }

    public function byId(int $sellerProfileId): Builder
    {
        return SellerProfile::query()->whereKey($sellerProfileId);
    }

    public function byStoreName(string $storeName, ?int $exceptProfileId): Builder
    {
        return SellerProfile::query()
            ->where('store_name', $storeName)
            ->when(
                $exceptProfileId !== null,
                fn (Builder $query): Builder => $query->whereKeyNot($exceptProfileId),
            );
    }

    public function forAdmin(
        ?SellerProfileStatus $status,
        ?BankVerificationStatus $bank,
        bool $namePendingOnly = false,
    ): Builder {
        return SellerProfile::query()
            ->when(
                $namePendingOnly,
                fn (Builder $query): Builder => $query->whereNotNull('pending_store_name'),
            )
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where('status', $status->value),
            )
            ->when(
                $bank !== null,
                fn (Builder $query): Builder => $query->where('bank_verification_status', $bank->value),
            )
            ->with('user:id,name,email')
            // The ordering goes LAST, and the two cases are exclusive:
            // a queue is oldest-first so nobody waits behind a later
            // arrival, a browse list is newest-first. Chaining reorder()
            // and then latest() would have quietly produced both.
            ->when(
                $namePendingOnly,
                fn (Builder $query): Builder => $query->oldest('store_name_requested_at'),
                fn (Builder $query): Builder => $query->latest('id'),
            );
    }
}
