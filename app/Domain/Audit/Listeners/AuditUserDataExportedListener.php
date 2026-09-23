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
use App\Domain\User\Events\UserDataExported;
use App\Models\User;

final class AuditUserDataExportedListener
{
    public function handle(UserDataExported $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::DATA_EXPORTED,
                category: AuditCategory::USER_MANAGEMENT,
                // NOTICE rather than INFO — a data export isn't dangerous,
                // but it's the kind of event worth standing out a little
                // in the log (matches account-boundary events like this
                // more than routine reads).
                severity: AuditSeverity::NOTICE,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->user->id,
                description: 'User downloaded a copy of their own account data.',
                occurredAt: now(),
            ),
        );
    }
}
