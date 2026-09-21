<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Services\PreferenceService;
use App\Models\UserPreference;

final readonly class GetPreferencesAction
{
    public function __construct(
        private PreferenceService $preferenceService,
    ) {}

    public function execute(int $userId): ?UserPreference
    {
        return $this->preferenceService->getPreferences($userId);
    }
}
