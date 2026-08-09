<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Contracts;

use App\Domain\Auth\Repositories\DTO\CreatePasswordHistoryData;
use App\Models\PasswordHistory;
use Illuminate\Database\Eloquent\Collection;

interface PasswordHistoryRepositoryInterface
{
    public function create(
        CreatePasswordHistoryData $data,
    ): PasswordHistory;

    public function latestForUser(
        string $userId,
        int $limit,
    ): Collection;

    public function countByUser(
        string $userId,
    ): int;

    public function oldestForUser(
        string $userId,
    ): ?PasswordHistory;

    public function delete(
        PasswordHistory $history,
    ): void;
}
