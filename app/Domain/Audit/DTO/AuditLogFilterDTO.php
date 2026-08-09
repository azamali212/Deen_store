<?php

declare(strict_types=1);

namespace App\Domain\Audit\DTO;

use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Enums\AuditCategory;
use App\Domain\Audit\Enums\AuditSeverity;
use App\Domain\Audit\Enums\AuditStatus;

final readonly class AuditLogFilterDTO
{
    public function __construct(
        public ?AuditAction $action = null,
        public ?AuditCategory $category = null,
        public ?AuditSeverity $severity = null,
        public ?AuditStatus $status = null,
        public int|string|null $actorId = null,
        public ?string $actorType = null,
        public int|string|null $subjectId = null,
        public ?string $subjectType = null,
        public ?string $panel = null,
        public ?string $ipAddress = null,
        public ?string $requestId = null,
        public ?string $correlationId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public int $perPage = 25,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            action: isset($data['action'])
                ? AuditAction::from((string) $data['action'])
                : null,
            category: isset($data['category'])
                ? AuditCategory::from((string) $data['category'])
                : null,
            severity: isset($data['severity'])
                ? AuditSeverity::from((string) $data['severity'])
                : null,
            status: isset($data['status'])
                ? AuditStatus::from((string) $data['status'])
                : null,
            actorId: $data['actor_id'] ?? null,
            actorType: isset($data['actor_type'])
                ? (string) $data['actor_type']
                : null,
            subjectId: $data['subject_id'] ?? null,
            subjectType: isset($data['subject_type'])
                ? (string) $data['subject_type']
                : null,
            panel: isset($data['panel'])
                ? (string) $data['panel']
                : null,
            ipAddress: isset($data['ip_address'])
                ? (string) $data['ip_address']
                : null,
            requestId: isset($data['request_id'])
                ? (string) $data['request_id']
                : null,
            correlationId: isset($data['correlation_id'])
                ? (string) $data['correlation_id']
                : null,
            dateFrom: isset($data['date_from'])
                ? (string) $data['date_from']
                : null,
            dateTo: isset($data['date_to'])
                ? (string) $data['date_to']
                : null,
            perPage: (int) ($data['per_page'] ?? 25),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'action' => $this->action?->value,
            'category' => $this->category?->value,
            'severity' => $this->severity?->value,
            'status' => $this->status?->value,
            'actor_id' => $this->actorId,
            'actor_type' => $this->actorType,
            'subject_id' => $this->subjectId,
            'subject_type' => $this->subjectType,
            'panel' => $this->panel,
            'ip_address' => $this->ipAddress,
            'request_id' => $this->requestId,
            'correlation_id' => $this->correlationId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
