<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Contracts;

use App\Domain\Auth\Repositories\DTO\CreateRecoveryCodeData;
use App\Domain\Auth\Repositories\DTO\UpdateTwoFactorData;
use App\Models\TwoFactorRecoveryCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface TwoFactorRepositoryInterface
{
    public function findUserById(
        string $userId,
    ): ?User;

    public function findUserByEmail(
        string $email,
    ): ?User;

    public function updateTwoFactor(
        User $user,
        UpdateTwoFactorData $data,
    ): User;

    public function createRecoveryCode(
        CreateRecoveryCodeData $data,
    ): TwoFactorRecoveryCode;

    public function recoveryCodes(
        User $user,
    ): Collection;

    public function deleteRecoveryCodes(
        User $user,
    ): void;

    public function markRecoveryCodeAsUsed(
        TwoFactorRecoveryCode $code,
    ): void;

    public function existsEnabled(
        User $user,
    ): bool;

    public function updateLastVerified(
        User $user,
    ): void;
}
