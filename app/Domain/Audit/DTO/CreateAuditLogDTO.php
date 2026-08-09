<?php

declare(strict_types=1);

namespace App\Domain\Audit\DTO;

use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Enums\AuditCategory;
use App\Domain\Audit\Enums\AuditSeverity;
use App\Domain\Audit\Enums\AuditStatus;
use Carbon\CarbonInterface;

final readonly class CreateAuditLogDTO
{
    public function __construct(
        public AuditAction $action,
        public AuditCategory $category,
        public AuditSeverity $severity,
        public AuditStatus $status,
        public AuditContextDTO $context,
        public ?string $subjectType = null,
        public int|string|null $subjectId = null,
        public ?string $description = null,
        public ?array $oldValues = null,
        public ?array $newValues = null,
        public ?array $metadata = null,
        public ?CarbonInterface $occurredAt = null,
    ) {}
}
