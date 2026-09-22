<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Moderation\Actions\EnforceContentModerationAction;
use App\Domain\User\DTO\UpdateProfileDTO;
use App\Domain\User\Events\ProfileUpdated;
use App\Domain\User\Services\ProfileService;
use App\Models\UserProfile;

final readonly class UpdateProfileAction
{
    public function __construct(
        private ProfileService $profileService,
        private EnforceContentModerationAction $moderation,
    ) {}

    public function execute(int $userId, UpdateProfileDTO $dto): UserProfile
    {
        // Blocks (throws) before anything is touched if the AI flags any
        // field — the request never reaches profileService->updateProfile().
        $this->moderation->enforceText($userId, [
            'username' => $dto->username->value(),
            'bio' => $dto->bio,
            'website_url' => $dto->websiteUrl,
            'occupation' => $dto->occupation,
            'company_name' => $dto->companyName,
        ]);

        $profile = $this->profileService->updateProfile($userId, $dto);

        event(new ProfileUpdated($profile));

        return $profile;
    }
}
