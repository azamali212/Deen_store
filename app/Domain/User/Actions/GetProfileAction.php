<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Services\ProfileService;
use App\Models\UserProfile;

final readonly class GetProfileAction
{
    public function __construct(
        private ProfileService $profileService,
    ) {}

    public function execute(int $userId): ?UserProfile
    {
        return $this->profileService->getProfile($userId);
    }
}
