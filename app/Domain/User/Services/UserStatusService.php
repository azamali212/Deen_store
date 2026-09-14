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
