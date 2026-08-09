<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Queries;

use App\Models\PasswordHistory;
use Illuminate\Database\Eloquent\Collection;

final readonly class PasswordHistoryQuery
{
    public function latestForUser(
        string $userId,
        int $limit,
    ): Collection {

        return PasswordHistory::query()
            ->where(
                'user_id',
                $userId,
            )
            ->latest(
                'created_at',
            )
            ->limit(
                $limit,
            )
            ->get();
    }

    public function countByUser(
        string $userId,
    ): int {

        return PasswordHistory::query()
            ->where(
                'user_id',
                $userId,
            )
            ->count();
    }

    public function oldestForUser(
        string $userId,
    ): ?PasswordHistory {

        return PasswordHistory::query()
            ->where(
                'user_id',
                $userId,
            )
            ->oldest(
                'created_at',
            )
            ->first();
    }
}
