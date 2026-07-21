<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTO\UnlockAccountDTO;
use App\Domain\Auth\Events\AccountLocked;
use App\Domain\Auth\Events\AccountUnlocked;
use App\Domain\Auth\Exceptions\AccountLockedException;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Models\User;

final readonly class AccountLockoutService
{
    public function __construct(
        private AuthRepositoryInterface $repository,
    ) {}

    public function ensureNotLocked(User $user): void
    {
        if ($user->locked_at === null) {
            return;
        }

        if ($user->locked_until !== null && $user->locked_until->isPast()) {
            $this->unlock($user);

            return;
        }

        throw new AccountLockedException(
            retryAfter: $user->locked_until !== null
                ? (int) $user->locked_until->diffInSeconds(now())
                : null,
            lockedUntil: $user->locked_until?->toISOString(),
        );
    }

    public function recordFailedAttempt(User $user): void
    {
        $attempts = $user->failed_login_attempts + 1;

        // dd([
        //  'attempts' => $attempts,
        // 'max_attempts' => $this->maxAttempts(),
        // 'config' => config('auth_security.lockout.max_failed_attempts'),
        // ]);

        $this->repository->updateUser(
            $user,
            [
                'failed_login_attempts' => $attempts,
                'last_failed_login_at' => now(),
            ],
        );

        if ($attempts < $this->maxAttempts()) {
            return;
        }

        $this->lock($user);
    }

    public function lock(User $user, ?string $reason = null): void
    {
        $lockedUntil = now()->addMinutes($this->durationMinutes());

        $lockReason = $reason ?? 'Maximum failed login attempts exceeded.';

        $this->repository->updateUser(
            $user,
            [
                'locked_at' => now(),
                'locked_until' => $lockedUntil,
                'lock_reason' => $lockReason,
            ],
        );

        event(
            new AccountLocked(
                user: $user->fresh(),
                reason: $lockReason,
                lockedUntil: $lockedUntil->toISOString(),
            ),
        );
    }

    public function unlock(User $user): void
    {
        $this->repository->updateUser(
            $user,
            [
                'failed_login_attempts' => 0,
                'locked_at' => null,
                'locked_until' => null,
                'lock_reason' => null,
                'last_failed_login_at' => null,
            ],
        );
    }

    public function resetFailedAttempts(User $user): void
    {
        if ($user->failed_login_attempts === 0) {
            return;
        }

        $this->repository->updateUser(
            $user,
            [
                'failed_login_attempts' => 0,
                'last_failed_login_at' => null,
            ],
        );
    }

    private function maxAttempts(): int
    {
        return (int) config(
            'auth_security.lockout.max_failed_attempts',
            10,
        );
    }

    private function durationMinutes(): int
    {
        return (int) config(
            'auth_security.lockout.duration_minutes',
            60,
        );
    }

    public function unlockByAdmin(UnlockAccountDTO $dto): User
    {
        $user = $this->repository->findUserById($dto->userId);

        if (! $user instanceof User) {
            throw new \RuntimeException('User not found.');
        }

        if ($user->locked_at === null) {
            throw new \RuntimeException('Account is not locked.');
        }

        $this->repository->updateUser(
            $user,
            [
                'failed_login_attempts' => 0,
                'locked_at' => null,
                'locked_until' => null,
                'lock_reason' => null,
                'last_failed_login_at' => null,
            ],
        );

        event(
            new AccountUnlocked(
                user: $user,
                unlockedByUserId: $dto->unlockedByUserId,
                reason: $dto->reason,
            ),
        );

        return $user->fresh();
    }
}