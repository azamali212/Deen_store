<?php

declare(strict_types=1);

namespace App\Domain\Audit\Repositories\DTO;

use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Enums\AuditCategory;
use App\Domain\Audit\Enums\AuditSeverity;
use App\Domain\Audit\Enums\AuditStatus;
use Carbon\CarbonInterface;

final readonly class CreateAuditLogData
{
    public function __construct(
        public AuditAction $action,
        public AuditCategory $category,
        public AuditSeverity $severity,
        public AuditStatus $status,
        public ?string $actorType = null,
        public int|string|null $actorId = null,
        public ?string $subjectType = null,
        public int|string|null $subjectId = null,
        public ?string $description = null,
        public ?array $oldValues = null,
        public ?array $newValues = null,
        public ?array $metadata = null,
        public ?string $panel = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $deviceName = null,
        public ?string $requestId = null,
        public ?string $correlationId = null,
        public ?CarbonInterface $occurredAt = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'actor_type' => $this->actorType,
            'actor_id' => $this->actorId,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'action' => $this->action->value,
            'category' => $this->category->value,
            'severity' => $this->severity->value,
            'status' => $this->status->value,
            'description' => $this->description,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'metadata' => $this->metadata,
            'panel' => $this->panel,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'device_name' => $this->deviceName,
            'request_id' => $this->requestId,
            'correlation_id' => $this->correlationId,
            'occurred_at' => $this->occurredAt ?? now(),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
