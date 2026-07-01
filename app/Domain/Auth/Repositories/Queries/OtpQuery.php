<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Queries;

use App\Domain\Auth\Enums\OtpPurpose;
use App\Models\LoginOtp;
use Illuminate\Database\Eloquent\Builder;

final class OtpQuery
{
    public function latestForIdentifier(
        string $identifier,
        OtpPurpose $purpose
    ): Builder {
        return LoginOtp::query()
            ->where('identifier', strtolower(trim($identifier)))
            ->where('purpose', $purpose->value)
            ->latest();
    }

    public function validForIdentifier(
        string $identifier,
        string $codeHash,
        OtpPurpose $purpose
    ): Builder {
        return LoginOtp::query()
            ->where('identifier', strtolower(trim($identifier)))
            ->where('code', $codeHash)
            ->where('purpose', $purpose->value)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest();
    }

    public function valid(
        int|string $userId,
        string $purpose
    ): Builder {
        return LoginOtp::query()
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest();
    }

    public function active(
        int|string $userId,
        string $purpose
    ): Builder {
        return LoginOtp::query()
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now());
    }

    public function activeForPurpose(
        int|string $userId,
        string $purpose
    ): Builder {
        return LoginOtp::query()
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->whereNull('verified_at');
    }

    public function expired(): Builder
    {
        return LoginOtp::query()
            ->where('expires_at', '<=', now())
            ->whereNull('verified_at');
    }
}