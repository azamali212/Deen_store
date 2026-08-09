<?php

declare(strict_types=1);

namespace App\Domain\Audit\Repositories;

use App\Domain\Audit\Repositories\Contracts\AuditRepositoryInterface;
use App\Domain\Audit\Repositories\DTO\CreateAuditLogData;
use App\Domain\Audit\Repositories\Queries\AuditLogQuery;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class AuditRepository implements AuditRepositoryInterface
{
    public function __construct(
        private AuditLogQuery $queries,
    ) {}

    public function create(CreateAuditLogData $data): AuditLog
    {
        return AuditLog::query()->create(
            $data->toArray(),
        );
    }

    public function findByUuid(string $uuid): ?AuditLog
    {
        return $this->queries
            ->byUuid($uuid)
            ->first();
    }

    public function paginate(
        array $filters = [],
        int $perPage = 25,
    ): LengthAwarePaginator {
        return $this->queries
            ->filtered($filters)
            ->paginate($perPage);
    }
}
