<?php

declare(strict_types=1);

namespace App\Domain\Audit\Listeners;

use App\Domain\Audit\DTO\AuditContextDTO;
use App\Domain\Audit\DTO\CreateAuditLogDTO;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Enums\AuditCategory;
use App\Domain\Audit\Enums\AuditSeverity;
use App\Domain\Audit\Enums\AuditStatus;
use App\Domain\Audit\Jobs\WriteAuditLogJob;
use App\Domain\User\Events\UserSuspended;
use App\Models\User;

final class AuditUserSuspendedListener
{
    public function handle(UserSuspended $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::USER_DEACTIVATED,
                category: AuditCategory::USER_MANAGEMENT,
                severity: AuditSeverity::WARNING,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->user->id,
                description: 'User account was suspended.',
                newValues: [
                    'status' => $event->user->status?->value,
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
