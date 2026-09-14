<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories\Queries;

use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Builder;

final class UserAddressQuery
{
    public function byId(int $addressId): Builder
    {
        return UserAddress::query()
            ->whereKey($addressId);
    }

    public function byIdForUser(int $userId, int $addressId): Builder
    {
        return UserAddress::query()
            ->where('user_id', $userId)
            ->whereKey($addressId);
    }

    public function forUser(int $userId): Builder
    {
        return UserAddress::query()
            ->where('user_id', $userId)
            ->latest();
    }

    public function defaultForUser(int $userId): Builder
    {
        return UserAddress::query()
            ->where('user_id', $userId)
            ->where('is_default', true);
    }
}
