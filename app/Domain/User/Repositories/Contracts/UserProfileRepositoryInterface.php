<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories\Contracts;

use App\Domain\User\DTO\UpdateProfileDTO;
use App\Models\UserProfile;

interface UserProfileRepositoryInterface
{
    public function findByUserId(int $userId): ?UserProfile;

    public function findByUsername(string $username): ?UserProfile;

    public function existsByUsername(string $username): bool;

    public function create(int $userId, UpdateProfileDTO $dto): UserProfile;

    public function update(int $userId, UpdateProfileDTO $dto): UserProfile;

    public function updateAvatar(int $userId, string $avatarPath, string $avatarProvider): UserProfile;

    public function removeAvatar(int $userId): UserProfile;

    public function updateProfileCompletion(int $userId, int $percentage): UserProfile;

    public function delete(int $userId): bool;
}
