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
use App\Domain\Moderation\Events\ProfileContentBlocked;
use App\Models\User;

final class AuditProfileContentBlockedListener
{
    public function handle(ProfileContentBlocked $event): void
    {
        // Fires synchronously inside the live profile-update / avatar-upload
        // request (the whole point is that it blocks before saving), so this
        // correctly captures the actor/IP of the person who tried to save it.
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::PROFILE_CONTENT_BLOCKED,
                category: AuditCategory::MODERATION,
                severity: AuditSeverity::WARNING,
                status: AuditStatus::DENIED,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->flag->user_id,
                description: 'AI blocked a profile update/avatar before it was saved.',
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
