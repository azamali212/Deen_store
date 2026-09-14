<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories\Contracts;

use App\Domain\User\DTO\UpdatePreferenceDTO;
use App\Models\UserPreference;

interface UserPreferenceRepositoryInterface
{
    public function findByUserId(int $userId): ?UserPreference;

    public function create(int $userId, UpdatePreferenceDTO $dto): UserPreference;

    public function update(int $userId, UpdatePreferenceDTO $dto): UserPreference;

    public function updateOrCreate(int $userId, UpdatePreferenceDTO $dto): UserPreference;

    public function delete(int $userId): bool;
}
