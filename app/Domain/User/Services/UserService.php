<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\User\DTO\UpdateUserDTO;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Models\User;

final readonly class UserService
{
    public function __construct(
        private AuthRepositoryInterface $users,
        private UserValidationService $validation,
    ) {}

    public function getUser(int $userId): User
    {
        return $this->findOrFail($userId);
    }

    public function updateUser(int $userId, UpdateUserDTO $dto): User
    {
        $user = $this->findOrFail($userId);

        // Only re-check uniqueness when the value actually changed —
        // otherwise a user saving their own unchanged email would trip
        // "already taken" against themselves.
        if (strtolower($user->email) !== $dto->email) {
            $this->validation->ensureEmailIsAvailable($dto->email, $userId);
        }

        if ($dto->phone !== null && $dto->phone->value() !== $user->phone) {
            $this->validation->ensurePhoneIsAvailable($dto->phone->value(), $userId);
        }

        $this->users->updateUser($user, [
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone?->value(),
        ]);

        return $user->refresh();
    }

    public function deleteUser(int $userId): User
    {
        $user = $this->findOrFail($userId);

        $this->users->deleteUser($user);

        // A deleted account must lose access immediately, same as a
        // suspended/banned one — an existing session/token must not keep
        // working just because it hasn't expired yet.
        $this->users->terminateAllSessions($userId);

        return $user;
    }

    public function restoreUser(int $userId): User
    {
        $user = $this->users->restoreUser($userId);

        if ($user === null) {
            throw UserNotFoundException::withId($userId);
        }

        return $user;
    }

    private function findOrFail(int $userId): User
    {
        $user = $this->users->findUserById($userId);

        if ($user === null) {
            throw UserNotFoundException::withId($userId);
        }

        return $user;
    }
}
