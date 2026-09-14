<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\Exceptions\ProfileNotFoundException;
use App\Domain\User\Repositories\Contracts\UserProfileRepositoryInterface;
use App\Domain\User\Support\ProfileCompletionCalculator;
use App\Models\UserProfile;

final readonly class ProfileCompletionService
{
    public function __construct(
        private UserProfileRepositoryInterface $profiles,
        private ProfileCompletionCalculator $calculator,
    ) {}

    public function recalculate(int $userId): UserProfile
    {
        $profile = $this->profiles->findByUserId($userId);

        if ($profile === null) {
            throw ProfileNotFoundException::withUserId($userId);
        }

        $percentage = $this->calculator->calculate($profile);

        return $this->profiles->updateProfileCompletion($userId, $percentage);
    }
}
