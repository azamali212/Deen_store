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

    public function forAdmin(?SellerProfileStatus $status, ?BankVerificationStatus $bank): Builder
    {
        return SellerProfile::query()
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where('status', $status->value),
            )
            ->when(
                $bank !== null,
                fn (Builder $query): Builder => $query->where('bank_verification_status', $bank->value),
            )
            ->with('user:id,name,email')
            ->latest('id');
    }
}
