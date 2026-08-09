<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Audit\DTO\AuditContextDTO;
use App\Domain\Audit\DTO\CreateAuditLogDTO;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Enums\AuditCategory;
use App\Domain\Audit\Enums\AuditSeverity;
use App\Domain\Audit\Enums\AuditStatus;
use App\Domain\Audit\Services\AuditService;
use App\Domain\Auth\Events\PasswordHistoryCreated;

final readonly class LogPasswordHistoryCreatedListener
{
    public function __construct(
        private AuditService $auditService,
    ) {}

    public function handle(
        PasswordHistoryCreated $event,
    ): void {

        $this->auditService->record(
            new CreateAuditLogDTO(
                action: AuditAction::PASSWORD_CHANGED,
                category: AuditCategory::AUTHENTICATION,
                severity: AuditSeverity::INFO,
                status: AuditStatus::SUCCESS,

                context: new AuditContextDTO(
                    actorType: $event->history->user::class,
                    actorId: (string) $event->history->user_id,
                ),

                subjectType: $event->history::class,
                subjectId: (string) $event->history->id,

                description: 'Password history stored.',

                metadata: [
                    'history_id' => $event->history->id,
                ],

                occurredAt: now(),
            ),
        );
    }
}
