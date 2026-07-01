<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Queries;

use App\Models\EmailVerification;
use Illuminate\Database\Eloquent\Builder;

final class EmailVerificationQuery
{
    public function byToken(
        string $token
    ): Builder {
        return EmailVerification::query()
            ->where('token', $token);
    }

    public function forUser(
        int|string $userId
    ): Builder {
        return EmailVerification::query()
            ->where('user_id', $userId)
            ->latest();
    }

    public function valid(
        int|string $userId
    ): Builder {
        return EmailVerification::query()
            ->where('user_id', $userId)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest();
    }

    public function verified(
        int|string $userId
    ): Builder {
        return EmailVerification::query()
            ->where('user_id', $userId)
            ->whereNotNull('verified_at')
            ->latest();
    }

    public function expired(): Builder
    {
        return EmailVerification::query()
            ->where('expires_at', '<', now());
    }

    public function active(int|string $userId): Builder 
    {
        return EmailVerification::query()
            ->where(
                'user_id',
                $userId
            )
            ->whereNull(
                'verified_at'
            )
            ->where(
                'expires_at',
                '>',
                now()
            );
    }
}