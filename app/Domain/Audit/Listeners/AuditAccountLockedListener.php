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
use App\Domain\Auth\Events\AccountLocked;
use App\Models\User;

final readonly class AuditAccountLockedListener
{
    public function __construct(
        private CreateAuditLogAction $action,
    ) {}

    public function handle(AccountLocked $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(
                request(),
                panel: $event->panel ?? null,
                deviceName: $event->deviceName ?? null,
            )
            : AuditContextDTO::system();

        $this->action->execute(
            new CreateAuditLogDTO(
                action: AuditAction::ACCOUNT_LOCKED,
                category: AuditCategory::SECURITY,
                severity: AuditSeverity::WARNING,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->user->id,
                description: 'User account was locked.',
                newValues: [
                    'locked_at' => $event->user->locked_at?->toISOString(),
                    'locked_until' => $event->lockedUntil,
                    'lock_reason' => $event->reason,
                ],
                metadata: [
                    'reason' => $event->reason,
                    'email' => $event->user->email,
                ],
                occurredAt: now(),
            ),
        );
    }
}
