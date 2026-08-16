<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories;

use App\Domain\Auth\Repositories\Contracts\TwoFactorRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreateRecoveryCodeData;
use App\Domain\Auth\Repositories\DTO\UpdateTwoFactorData;
use App\Domain\Auth\Repositories\Queries\TwoFactorQuery;
use App\Models\TwoFactorRecoveryCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class TwoFactorRepository implements TwoFactorRepositoryInterface
{
    public function __construct(
        private TwoFactorQuery $query,
    ) {}

    public function findUserById(
        string $userId,
    ): ?User {

        return $this->query
            ->findUserById(
                $userId,
            );
    }

    public function findUserByEmail(
        string $email,
    ): ?User {

        return $this->query
            ->findUserByEmail(
                $email,
            );
    }

    public function existsEnabled(
        User $user,
    ): bool {

        return $this->query
            ->existsEnabled(
                $user,
            );
    }

    public function updateTwoFactor(
        User $user,
        UpdateTwoFactorData $data,
    ): User {

        $user->update(
            $data->toArray(),
        );

        return $user->refresh();
    }

    public function updateLastVerified(
        User $user,
    ): void {

        $user->update([
            'two_factor_last_verified_at' => now(),
        ]);
    }

    public function createRecoveryCode(
        CreateRecoveryCodeData $data,
    ): TwoFactorRecoveryCode {

        return TwoFactorRecoveryCode::query()
            ->create(
                $data->toArray(),
            );
    }

    public function recoveryCodes(
        User $user,
    ): Collection {

        return $this->query
            ->recoveryCodes(
                $user,
            );
    }

    public function findRecoveryCode(
        User $user,
        string $code,
    ): ?TwoFactorRecoveryCode {

        return $this->query
            ->findRecoveryCode(
                $user,
                $code,
            );
    }

    public function markRecoveryCodeAsUsed(
        TwoFactorRecoveryCode $code,
    ): void {

        $code->update([
            'used_at' => now(),
        ]);
    }

    public function deleteRecoveryCodes(
        User $user,
    ): void {

        TwoFactorRecoveryCode::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->delete();
    }
}
