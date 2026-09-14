<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\DTO\UpdatePreferenceDTO;
use App\Domain\User\Services\PreferenceService;
use App\Models\UserPreference;

final readonly class UpdatePreferencesAction
{
    public function __construct(
        private PreferenceService $preferenceService,
    ) {}

    public function execute(int $userId, UpdatePreferenceDTO $dto): UserPreference
    {
        return $this->preferenceService->savePreferences($userId, $dto);
    }
}
