<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Services\ProfileCompletionService;
use App\Models\UserProfile;

final readonly class CalculateProfileCompletionAction
{
    public function __construct(
        private ProfileCompletionService $completionService,
    ) {}

    public function execute(int $userId): UserProfile
    {
        return $this->completionService->recalculate($userId);
    }
}
