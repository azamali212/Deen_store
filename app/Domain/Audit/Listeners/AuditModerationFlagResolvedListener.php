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
use App\Domain\Moderation\Events\ModerationFlagResolved;
use App\Models\User;

final class AuditModerationFlagResolvedListener
{
    public function handle(ModerationFlagResolved $event): void
    {
        // This one DOES fire inside a live admin HTTP request (the resolve
        // endpoint), so this correctly captures the reviewing admin's
        // actor/IP via AuditContextDTO::fromRequest().
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::MODERATION_FLAG_RESOLVED,
                category: AuditCategory::MODERATION,
                severity: AuditSeverity::NOTICE,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->flag->user_id,
                description: 'A profile moderation flag was reviewed.',
                newValues: [
                    'flag_id' => $event->flag->id,
                    'resolution' => $event->flag->status->value,
                    'reviewed_by' => $event->flag->reviewed_by,
                ],
                metadata: [
                    'review_notes' => $event->flag->review_notes,
                ],
                occurredAt: now(),
            ),
        );
    }
}
