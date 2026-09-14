<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Services\AvatarService;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;

final readonly class UploadAvatarAction
{
    public function __construct(
        private AvatarService $avatarService,
    ) {}

    public function execute(int $userId, UploadedFile $file): UserProfile
    {
        return $this->avatarService->uploadAvatar($userId, $file);
    }
}
