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
use App\Domain\Moderation\Events\ProfileFlaggedForReview;
use App\Models\User;

final class AuditProfileFlaggedListener
{
    public function handle(ProfileFlaggedForReview $event): void
    {
        // This event is fired from inside ModerateProfileContentJob, which
        // always runs on the queue worker (console) — never a live HTTP
        // request — so this correctly falls through to AuditContextDTO::system()
        // every time, same as every other queue-originated audit entry.
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::PROFILE_FLAGGED,
                category: AuditCategory::MODERATION,
                severity: AuditSeverity::WARNING,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->flag->user_id,
                description: 'AI flagged this profile for moderation review.',
                newValues: [
                    'flag_id' => $event->flag->id,
                    'severity' => $event->flag->severity?->value,
                    'flagged_fields' => array_keys($event->flag->flagged_fields),
                ],
                metadata: [
                    'ai_summary' => $event->flag->ai_summary,
                ],
                occurredAt: now(),
            ),
        );
    }
}
