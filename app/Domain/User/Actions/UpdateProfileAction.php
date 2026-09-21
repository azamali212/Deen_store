<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\DTO\UpdateProfileDTO;
use App\Domain\User\Events\ProfileUpdated;
use App\Domain\User\Services\ProfileService;
use App\Models\UserProfile;

final readonly class UpdateProfileAction
{
    public function __construct(
        private ProfileService $profileService,
    ) {}

    public function execute(int $userId, UpdateProfileDTO $dto): UserProfile
    {
        $profile = $this->profileService->updateProfile($userId, $dto);

        event(new ProfileUpdated($profile));

        return $profile;
    }
}
