<?php

declare(strict_types=1);

namespace App\Domain\Audit\Actions;

use App\Domain\Audit\DTO\CreateAuditLogDTO;
use App\Domain\Audit\Services\AuditService;
use App\Models\AuditLog;

final readonly class CreateAuditLogAction
{
    public function __construct(
        private AuditService $service,
    ) {}

    public function execute(CreateAuditLogDTO $dto): AuditLog
    {
        return $this->service->record(
            $dto,
        );
    }
}
