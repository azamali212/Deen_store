<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\Auth\Enums\UserAccountStatus;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\User\DTO\UserStatusDTO;
use App\Domain\User\Exceptions\UserAlreadySuspendedException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Admin-initiated account status management (active/suspended/banned/etc.,
 * the `status` column) — a permanent, manual decision by an administrator.
 *
 * NOT to be confused with Auth\Services\AccountLockoutService, which is a
 * separate, automatic mechanism: temporary lockouts after repeated failed
 * login attempts (`locked_at` / `locked_until` / `failed_login_attempts`).
 * A user can be blocked by either mechanism independently — both must be
 * checked wherever "can this user log in?" matters (see User::isActive()
 * and AccountLockoutService's own lock check).
 */
final readonly class UserStatusService
{
    public function __construct(
        private AuthRepositoryInterface $users,
    ) {}

    public function changeStatus(UserStatusDTO $dto): User
    {
        $user = $this->users->findUserById($dto->userId);

        if ($user === null) {
            throw UserNotFoundException::withId($dto->userId);
        }

        if ($dto->status === UserAccountStatus::SUSPENDED && $user->status === UserAccountStatus::SUSPENDED) {
            throw UserAlreadySuspendedException::forUser($dto->userId);
        }

        return DB::transaction(function () use ($user, $dto): User {
            $this->users->updateUser($user, [
                'status' => $dto->status->value,
            ]);

            // A suspended/banned account must lose access immediately —
            // not just be blocked on its next login attempt.
            if ($dto->status->isBlocked()) {
                $this->users->terminateAllSessions($user->id);
            }

            return $user->refresh();
        });
    }

    public function suspend(int $userId, ?string $reason = null): User
    {
        return $this->changeStatus(new UserStatusDTO($userId, UserAccountStatus::SUSPENDED, $reason));
    }

    public function activate(int $userId): User
    {
        return $this->changeStatus(new UserStatusDTO($userId, UserAccountStatus::ACTIVE, null));
    }

    public function ban(int $userId, ?string $reason = null): User
    {
        return $this->changeStatus(new UserStatusDTO($userId, UserAccountStatus::BANNED, $reason));
    }
}
