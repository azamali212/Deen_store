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
use App\Domain\User\Events\ConsentGranted;
use App\Domain\User\Events\ConsentWithdrawn;
use App\Models\UserConsent;

/**
 * The consent table is already its own permanent record, so this listener is
 * not there to preserve the fact. It is there so that a consent change shows
 * up in the SAME timeline as everything else the account did that day —
 * which is how anyone reconstructing an incident actually reads history.
 */
final class AuditConsentListener
{
    public function handle(ConsentGranted|ConsentWithdrawn $event): void
    {
        $consent = $event->consent;
        $granted = $event instanceof ConsentGranted;

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: $granted
                    ? AuditAction::USER_CONSENT_GRANTED
                    : AuditAction::USER_CONSENT_WITHDRAWN,
                category: AuditCategory::USER_MANAGEMENT,
                severity: AuditSeverity::INFO,
                status: AuditStatus::SUCCESS,
                context: app()->runningInConsole()
                    ? AuditContextDTO::system()
                    : AuditContextDTO::fromRequest(request()),
                subjectType: UserConsent::class,
                subjectId: (string) $consent->id,
                description: $granted
                    ? 'A user agreed to '.$consent->type->label().'.'
                    : 'A user withdrew consent for '.$consent->type->label().'.',
                newValues: [
                    'consent_type' => $consent->type->value,
                    'version' => $consent->version,
                    'granted_at' => $consent->granted_at?->toIso8601String(),
                    'withdrawn_at' => $consent->withdrawn_at?->toIso8601String(),
                ],
                occurredAt: now(),
            ),
        );
    }
}
