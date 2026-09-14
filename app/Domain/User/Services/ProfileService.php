<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\DTO\UpdateProfileDTO;
use App\Domain\User\Exceptions\InvalidUsernameException;
use App\Domain\User\Repositories\Contracts\UserProfileRepositoryInterface;
use App\Models\UserProfile;

final readonly class ProfileService
{
    public function __construct(
        private UserProfileRepositoryInterface $profiles,
    ) {}

    public function getProfile(int $userId): ?UserProfile
    {
        return $this->profiles->findByUserId($userId);
    }

    public function updateProfile(int $userId, UpdateProfileDTO $dto): UserProfile
    {
        $existing = $this->profiles->findByUserId($userId);

        $this->ensureUsernameIsAvailable($dto, $existing);

        return $existing === null
            ? $this->profiles->create($userId, $dto)
            : $this->profiles->update($userId, $dto);
    }

    public function deleteProfile(int $userId): bool
    {
        return $this->profiles->delete($userId);
    }

    private function ensureUsernameIsAvailable(UpdateProfileDTO $dto, ?UserProfile $existing): void
    {
        $newUsername = $dto->username->value();

        if ($existing !== null && $existing->username === $newUsername) {
            return;
        }

        if ($this->profiles->existsByUsername($newUsername)) {
            throw InvalidUsernameException::taken($newUsername);
        }
    }
}
