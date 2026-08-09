<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories;

use App\Domain\Auth\Repositories\Contracts\PasswordHistoryRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreatePasswordHistoryData;
use App\Domain\Auth\Repositories\Queries\PasswordHistoryQuery;
use App\Models\PasswordHistory;
use Illuminate\Database\Eloquent\Collection;

final readonly class PasswordHistoryRepository implements PasswordHistoryRepositoryInterface
{
    public function __construct(
        private PasswordHistoryQuery $query,
    ) {}

    public function create(
        CreatePasswordHistoryData $data,
    ): PasswordHistory {

        return PasswordHistory::query()
            ->create([
                'user_id' => $data->userId,
                'password' => $data->password,
            ]);
    }

    public function latestForUser(
        string $userId,
        int $limit,
    ): Collection {

        return $this->query
            ->latestForUser(
                $userId,
                $limit,
            );
    }

    public function countByUser(
        string $userId,
    ): int {

        return $this->query
            ->countByUser(
                $userId,
            );
    }

    public function oldestForUser(
        string $userId,
    ): ?PasswordHistory {

        return $this->query
            ->oldestForUser(
                $userId,
            );
    }

    public function delete(
        PasswordHistory $history,
    ): void {

        $history->delete();
    }
}
