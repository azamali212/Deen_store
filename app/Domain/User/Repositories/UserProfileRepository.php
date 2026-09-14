<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

use App\Domain\User\DTO\UpdateProfileDTO;
use App\Domain\User\Exceptions\ProfileNotFoundException;
use App\Domain\User\Repositories\Contracts\UserProfileRepositoryInterface;
use App\Domain\User\Repositories\Queries\UserProfileQuery;
use App\Models\UserProfile;

final class UserProfileRepository implements UserProfileRepositoryInterface
{
    public function __construct(
        private readonly UserProfileQuery $query,
    ) {}

    public function findByUserId(int $userId): ?UserProfile
    {
        return $this->query->byUserId($userId)->first();
    }

    public function findByUsername(string $username): ?UserProfile
    {
        return $this->query->byUsername($username)->first();
    }

    public function existsByUsername(string $username): bool
    {
        return $this->query->byUsername($username)->exists();
    }

    public function create(int $userId, UpdateProfileDTO $dto): UserProfile
    {
        return UserProfile::create([
            'user_id' => $userId,
            'username' => $dto->username->value(),
            'date_of_birth' => $dto->dateOfBirth,
            'gender' => $dto->gender,
            'bio' => $dto->bio,
            'website_url' => $dto->websiteUrl,
            'occupation' => $dto->occupation,
            'company_name' => $dto->companyName,
            'country_code' => $dto->countryCode,
            'timezone' => $dto->timezone,
            'locale' => $dto->locale,
            'profile_visibility' => $dto->profileVisibility,
        ]);
    }

    public function update(int $userId, UpdateProfileDTO $dto): UserProfile
    {
        $profile = $this->findOrFail($userId);

        $profile->update([
            'username' => $dto->username->value(),
            'date_of_birth' => $dto->dateOfBirth,
            'gender' => $dto->gender,
            'bio' => $dto->bio,
            'website_url' => $dto->websiteUrl,
            'occupation' => $dto->occupation,
            'company_name' => $dto->companyName,
            'country_code' => $dto->countryCode,
            'timezone' => $dto->timezone,
            'locale' => $dto->locale,
            'profile_visibility' => $dto->profileVisibility,
        ]);

        return $profile->refresh();
    }

    public function updateAvatar(int $userId, string $avatarPath, string $avatarProvider): UserProfile
    {
        $profile = $this->findOrFail($userId);

        $profile->update([
            'avatar_path' => $avatarPath,
            'avatar_provider' => $avatarProvider,
        ]);

        return $profile->refresh();
    }

    public function removeAvatar(int $userId): UserProfile
    {
        $profile = $this->findOrFail($userId);

        $profile->update([
            'avatar_path' => null,
            'avatar_provider' => 'local',
        ]);

        return $profile->refresh();
    }

    public function updateProfileCompletion(int $userId, int $percentage): UserProfile
    {
        $profile = $this->findOrFail($userId);

        $profile->update([
            'profile_completion' => $percentage,
        ]);

        return $profile->refresh();
    }

    public function delete(int $userId): bool
    {
        $profile = $this->findOrFail($userId);

        return (bool) $profile->delete();
    }

    private function findOrFail(int $userId): UserProfile
    {
        return $this->findByUserId($userId)
            ?? throw ProfileNotFoundException::withUserId($userId);
    }
}
