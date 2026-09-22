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
use App\Domain\Auth\Events\AccountUnlocked;
use App\Models\User;

final class AuditAccountUnlockedListener
{
    public function handle(AccountUnlocked $event): void
    {
        $context = app()->runningInConsole()
            ? AuditContextDTO::system()
            : AuditContextDTO::fromRequest(request());

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::ACCOUNT_UNLOCKED,
                category: AuditCategory::SECURITY,
                severity: AuditSeverity::NOTICE,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->user->id,
                description: 'User account was unlocked.',
                newValues: [
                    'failed_login_attempts' => 0,
                    'locked_at' => null,
                    'locked_until' => null,
                    'lock_reason' => null,
                ],
                metadata: [
                    'email' => $event->user->email,
                ],
                occurredAt: now(),
            ),
        );
    }
}
