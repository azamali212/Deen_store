<?php

declare(strict_types=1);

namespace App\Domain\Audit\Repositories\Queries;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;

final class AuditLogQuery
{
    public function base(): Builder
    {
        return AuditLog::query()->latest('occurred_at');
    }

    public function byUuid(string $uuid): Builder
    {
        return AuditLog::query()->where('uuid', $uuid);
    }

    public function filtered(array $filters): Builder
    {
        return $this->base()
            ->when(
                $filters['action'] ?? null,
                fn (Builder $query, string $action): Builder => $query->where('action', $action),
            )
            ->when(
                $filters['category'] ?? null,
                fn (Builder $query, string $category): Builder => $query->where('category', $category),
            )
            ->when(
                $filters['severity'] ?? null,
                fn (Builder $query, string $severity): Builder => $query->where('severity', $severity),
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('status', $status),
            )
            ->when(
                $filters['actor_id'] ?? null,
                fn (Builder $query, int|string $actorId): Builder => $query->where('actor_id', $actorId),
            )
            ->when(
                $filters['actor_type'] ?? null,
                fn (Builder $query, string $actorType): Builder => $query->where('actor_type', $actorType),
            )
            ->when(
                $filters['subject_id'] ?? null,
                fn (Builder $query, int|string $subjectId): Builder => $query->where('subject_id', $subjectId),
            )
            ->when(
                $filters['subject_type'] ?? null,
                fn (Builder $query, string $subjectType): Builder => $query->where('subject_type', $subjectType),
            )
            ->when(
                $filters['panel'] ?? null,
                fn (Builder $query, string $panel): Builder => $query->where('panel', $panel),
            )
            ->when(
                $filters['ip_address'] ?? null,
                fn (Builder $query, string $ipAddress): Builder => $query->where('ip_address', $ipAddress),
            )
            ->when(
                $filters['request_id'] ?? null,
                fn (Builder $query, string $requestId): Builder => $query->where('request_id', $requestId),
            )
            ->when(
                $filters['correlation_id'] ?? null,
                fn (Builder $query, string $correlationId): Builder => $query->where('correlation_id', $correlationId),
            )
            ->when(
                $filters['date_from'] ?? null,

                fn (Builder $query, string $dateFrom): Builder => $query->where('occurred_at', '>=', $dateFrom),

            )
            ->when(
                $filters['date_to'] ?? null,

                fn (Builder $query, string $dateTo): Builder => $query->where('occurred_at', '<=', $dateTo),

            );
    }
}
