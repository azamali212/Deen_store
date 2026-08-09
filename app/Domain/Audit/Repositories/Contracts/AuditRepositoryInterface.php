<?php

declare(strict_types=1);

namespace App\Domain\Audit\Repositories\Contracts;

use App\Domain\Audit\Repositories\DTO\CreateAuditLogData;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditRepositoryInterface
{
    public function create(CreateAuditLogData $data): AuditLog;

    public function findByUuid(string $uuid): ?AuditLog;

    public function paginate(
        array $filters = [],
        int $perPage = 25,
    ): LengthAwarePaginator;
}
