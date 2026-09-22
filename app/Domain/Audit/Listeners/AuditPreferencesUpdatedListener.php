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
use App\Domain\User\Events\PreferencesUpdated;
use App\Models\User;

final class AuditPreferencesUpdatedListener
{
    public function handle(PreferencesUpdated $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::PREFERENCES_UPDATED,
                category: AuditCategory::USER_MANAGEMENT,
                severity: AuditSeverity::INFO,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->preferences->user_id,
                description: 'User updated their preferences.',
                newValues: [
                    'language' => $event->preferences->language,
                    'currency' => $event->preferences->currency,
                    'theme' => $event->preferences->theme,
                    'email_notifications' => $event->preferences->email_notifications,
                    'sms_notifications' => $event->preferences->sms_notifications,
                    'push_notifications' => $event->preferences->push_notifications,
                    'marketing_notifications' => $event->preferences->marketing_notifications,
                ],
                occurredAt: now(),
            ),
        );
    }
}
