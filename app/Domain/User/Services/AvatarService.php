<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\Contracts\AvatarStorageInterface;
use App\Domain\User\DTO\UpdateAvatarDTO;
use App\Domain\User\Repositories\Contracts\UserProfileRepositoryInterface;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;

final readonly class AvatarService
{
    public function __construct(
        private UserProfileRepositoryInterface $profiles,
        private AvatarStorageInterface $storage,
    ) {}

    public function uploadAvatar(int $userId, UploadedFile $file): UserProfile
    {
        $previousPath = $this->profiles->findByUserId($userId)?->avatar_path;

        $storedPath = $this->storage->store($userId, $file);

        $dto = UpdateAvatarDTO::fromArray([
            'user_id' => $userId,
            'avatar_path' => $storedPath,
            'avatar_provider' => 'local',
        ]);

        $profile = $this->profiles->updateAvatar($userId, $dto->avatarPath->value(), $dto->avatarProvider);

        if ($previousPath !== null && $previousPath !== $storedPath) {
            $this->storage->delete($previousPath);
        }

        return $profile;
    }

    public function deleteAvatar(int $userId): UserProfile
    {
        $previousPath = $this->profiles->findByUserId($userId)?->avatar_path;

        $profile = $this->profiles->removeAvatar($userId);

        if ($previousPath !== null) {
            $this->storage->delete($previousPath);
        }

        return $profile;
    }
}
