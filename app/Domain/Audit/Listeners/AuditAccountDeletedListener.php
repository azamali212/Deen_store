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
use App\Domain\User\Events\UserAccountDeleted;
use App\Models\User;

final class AuditAccountDeletedListener
{
    public function handle(UserAccountDeleted $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::ACCOUNT_DELETED,
                category: AuditCategory::USER_MANAGEMENT,
                severity: AuditSeverity::CRITICAL,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->user->id,
                description: 'User account was deleted (soft delete).',
                newValues: [
                    'deleted_at' => $event->user->deleted_at?->toISOString(),
                ],
                metadata: [
                    'email' => $event->user->email,
                ],
                occurredAt: now(),
            ),
        );
    }
}
