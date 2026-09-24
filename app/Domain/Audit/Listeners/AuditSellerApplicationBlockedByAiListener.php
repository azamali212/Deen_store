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
use App\Domain\Seller\Events\SellerApplicationBlockedByAi;
use App\Models\User;

final class AuditSellerApplicationBlockedByAiListener
{
    public function handle(SellerApplicationBlockedByAi $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::SELLER_APPLICATION_BLOCKED_BY_AI,
                category: AuditCategory::SELLER_MANAGEMENT,
                severity: AuditSeverity::NOTICE,
                status: AuditStatus::DENIED,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->application->user_id,
                description: 'Seller application submit blocked by automated cross-checks.',
                newValues: [
                    'application_id' => $event->application->id,
                    'failed_checks' => $event->failures,
                ],
                occurredAt: now(),
            ),
        );
    }
}
