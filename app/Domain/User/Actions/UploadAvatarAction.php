<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Moderation\Actions\EnforceContentModerationAction;
use App\Domain\User\Events\AvatarUploaded;
use App\Domain\User\Services\AvatarService;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;

final readonly class UploadAvatarAction
{
    public function __construct(
        private AvatarService $avatarService,
        private EnforceContentModerationAction $moderation,
    ) {}

    public function execute(int $userId, UploadedFile $file): UserProfile
    {
        // Checked straight off the uploaded file's bytes, BEFORE it is ever
        // written to storage — a rejected image never touches disk, so
        // there is nothing to clean up if this throws.
        $this->moderation->enforceAvatar(
            $userId,
            (string) file_get_contents($file->getRealPath()),
            $file->getMimeType() ?: 'image/jpeg',
        );

        $profile = $this->avatarService->uploadAvatar($userId, $file);

        event(new AvatarUploaded($profile));

        return $profile;
    }
}
