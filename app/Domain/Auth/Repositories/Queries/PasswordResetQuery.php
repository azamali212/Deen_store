<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Queries;

use App\Models\PasswordReset;
use Illuminate\Database\Eloquent\Builder;

final class PasswordResetQuery
{
    public function byToken(
        string $token
    ): Builder {

        return PasswordReset::query()
            ->where(
                'token',
                $token
            );
    }

    public function active(
        int|string $userId
    ): Builder {

        return PasswordReset::query()
            ->where(
                'user_id',
                $userId
            )
            ->whereNull(
                'used_at'
            )
            ->where(
                'expires_at',
                '>',
                now()
            );
    }

    public function expired(): Builder
    {
        return PasswordReset::query()
            ->where(
                'expires_at',
                '<=',
                now()
            );
    }

    public function forUser(
        int|string $userId
    ): Builder {

        return PasswordReset::query()
            ->where(
                'user_id',
                $userId
            )
            ->latest();
    }
}