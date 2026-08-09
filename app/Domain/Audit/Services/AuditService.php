<?php

declare(strict_types=1);

namespace App\Domain\Audit\Services;

use App\Domain\Audit\DTO\AuditLogFilterDTO;
use App\Domain\Audit\DTO\CreateAuditLogDTO;
use App\Domain\Audit\Repositories\Contracts\AuditRepositoryInterface;
use App\Domain\Audit\Repositories\DTO\CreateAuditLogData;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class AuditService
{
    public function __construct(
        private AuditRepositoryInterface $repository,
        private AuditRedactionService $redactionService,
    ) {}

    public function record(CreateAuditLogDTO $dto): AuditLog
    {
        $oldValues = $this->redactionService->redact(
            $dto->oldValues,
        );

        $newValues = $this->redactionService->redact(
            $dto->newValues,
        );

        $metadata = $this->redactionService->redact(
            $dto->metadata,
        );

        return $this->repository->create(
            new CreateAuditLogData(
                action: $dto->action,
                category: $dto->category,
                severity: $dto->severity,
                status: $dto->status,
                actorType: $dto->context->actorType,
                actorId: $dto->context->actorId,
                subjectType: $dto->subjectType,
                subjectId: $dto->subjectId,
                description: $dto->description,
                oldValues: $oldValues,
                newValues: $newValues,
                metadata: $metadata,
                panel: $dto->context->panel,
                ipAddress: $dto->context->ipAddress,
                userAgent: $dto->context->userAgent,
                deviceName: $dto->context->deviceName,
                requestId: $dto->context->requestId,
                correlationId: $dto->context->correlationId,
                occurredAt: $dto->occurredAt,
            ),
        );
    }

    public function findByUuid(string $uuid): ?AuditLog
    {
        return $this->repository->findByUuid(
            $uuid,
        );
    }

    public function paginate(
        AuditLogFilterDTO $filters,
    ): LengthAwarePaginator {
        return $this->repository->paginate(
            filters: $filters->toArray(),
            perPage: $filters->perPage,
        );
    }
}
