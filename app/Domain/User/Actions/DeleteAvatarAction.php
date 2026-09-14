<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Services\AvatarService;
use App\Models\UserProfile;

final readonly class DeleteAvatarAction
{
    public function __construct(
        private AvatarService $avatarService,
    ) {}

    public function execute(int $userId): UserProfile
    {
        return $this->avatarService->deleteAvatar($userId);
    }
}
