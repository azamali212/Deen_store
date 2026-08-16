<?php

declare(strict_types=1);

namespace App\Domain\Audit\Listeners;

use App\Domain\Audit\Actions\CreateAuditLogAction;
use App\Domain\Audit\DTO\AuditContextDTO;
use App\Domain\Audit\DTO\CreateAuditLogDTO;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Enums\AuditCategory;
use App\Domain\Audit\Enums\AuditSeverity;
use App\Domain\Audit\Enums\AuditStatus;
use App\Domain\Auth\Events\RecoveryCodeUsed;
use App\Models\User;

final readonly class AuditRecoveryCodeUsedListener
{
    public function __construct(
        private CreateAuditLogAction $action,
    ) {}

    public function handle(
        RecoveryCodeUsed $event,
    ): void {

        $context = app()->runningInConsole()
            ? AuditContextDTO::system()
            : AuditContextDTO::fromRequest(
                request(),
            );

        $this->action->execute(
            new CreateAuditLogDTO(
                action: AuditAction::OTHER_SESSIONS_TERMINATED,
                category: AuditCategory::SECURITY,
                severity: AuditSeverity::WARNING,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->user->id,
                description: 'Recovery code used for authentication.',
                metadata: [
                    'recovery_code_uuid' => $event->recoveryCode->uuid,
                    'email' => $event->user->email,
                ],
                occurredAt: now(),
            ),
        );
    }
}