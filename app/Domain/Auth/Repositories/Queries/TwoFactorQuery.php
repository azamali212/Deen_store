<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Queries;

use App\Models\TwoFactorRecoveryCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class TwoFactorQuery
{
    public function findUserById(
        string $userId,
    ): ?User {

        return User::query()
            ->find($userId);
    }

    public function existsEnabled(
        User $user,
    ): bool {

        return User::query()
            ->whereKey($user->id)
            ->where('two_factor_enabled', true)
            ->exists();
    }

    public function recoveryCodes(
        User $user,
    ): Collection {

        return TwoFactorRecoveryCode::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findRecoveryCode(
        User $user,
        string $uuid,
    ): ?TwoFactorRecoveryCode {

        return TwoFactorRecoveryCode::query()
            ->where('user_id', $user->id)
            ->where('uuid', $uuid)
            ->first();
    }

    public function findUserByEmail(
        string $email,
    ): ?User {

        return User::query()
            ->where(
                'email',
                $email,
            )
            ->first();
    }
}
