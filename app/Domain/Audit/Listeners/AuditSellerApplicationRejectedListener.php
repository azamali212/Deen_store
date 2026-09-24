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
use App\Domain\Seller\Events\SellerApplicationRejected;
use App\Models\User;

final class AuditSellerApplicationRejectedListener
{
    public function handle(SellerApplicationRejected $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::SELLER_APPLICATION_REJECTED,
                category: AuditCategory::SELLER_MANAGEMENT,
                severity: AuditSeverity::NOTICE,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->application->user_id,
                description: 'Seller application rejected.',
                oldValues: [
                    'status' => 'pending',
                ],
                newValues: [
                    'application_id' => $event->application->id,
                    'store_name' => $event->application->store_name,
                    'status' => $event->application->status->value,
                    'rejection_reason' => $event->application->rejection_reason,
                    'reviewed_by' => $event->application->reviewed_by,
                ],
                occurredAt: now(),
            ),
        );
    }
}
