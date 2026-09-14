<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\DTO\UpdatePreferenceDTO;
use App\Domain\User\Repositories\Contracts\UserPreferenceRepositoryInterface;
use App\Models\UserPreference;

final readonly class PreferenceService
{
    public function __construct(
        private UserPreferenceRepositoryInterface $preferences,
    ) {}

    public function getPreferences(int $userId): ?UserPreference
    {
        return $this->preferences->findByUserId($userId);
    }

    public function savePreferences(int $userId, UpdatePreferenceDTO $dto): UserPreference
    {
        return $this->preferences->updateOrCreate($userId, $dto);
    }

    public function resetToDefaults(int $userId): UserPreference
    {
        return $this->preferences->updateOrCreate($userId, UpdatePreferenceDTO::fromArray([]));
    }

    public function deletePreferences(int $userId): bool
    {
        return $this->preferences->delete($userId);
    }
}
