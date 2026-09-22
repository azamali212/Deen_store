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
use App\Domain\Auth\Events\TwoFactorConfirmed;
use App\Models\User;

final class AuditTwoFactorConfirmedListener
{
    public function handle(TwoFactorConfirmed $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::TWO_FACTOR_CONFIRMED,
                category: AuditCategory::SECURITY,
                severity: AuditSeverity::NOTICE,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->user->id,
                description: 'Two-factor authentication setup was confirmed and activated.',
                newValues: [
                    'two_factor_confirmed_at' => $event->user->two_factor_confirmed_at?->toISOString(),
                ],
                metadata: [
                    'email' => $event->user->email,
                ],
                occurredAt: now(),
            ),
        );
    }
}
