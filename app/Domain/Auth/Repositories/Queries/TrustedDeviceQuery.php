<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Queries;

use App\Models\TrustedDevice;
use Illuminate\Database\Eloquent\Builder;

final class TrustedDeviceQuery
{
    public function forUser(int|string $userId): Builder
    {
        return TrustedDevice::query()
            ->where('user_id', $userId)
            ->latest('last_used_at');
    }

    public function byFingerprint(
        int|string $userId,
        string $fingerprint
    ): Builder {
        return TrustedDevice::query()
            ->where('user_id', $userId)
            ->where('fingerprint', $fingerprint);
    }

    public function trusted(
        int|string $userId,
        string $fingerprint
    ): Builder {
        return TrustedDevice::query()
            ->where('user_id', $userId)
            ->where('fingerprint', $fingerprint)
            ->where(function (Builder $query): void {
                $query->whereNull('trusted_until')
                    ->orWhere('trusted_until', '>', now());
            });
    }
}