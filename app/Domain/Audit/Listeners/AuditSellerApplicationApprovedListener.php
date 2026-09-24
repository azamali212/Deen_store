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
use App\Domain\Seller\Events\SellerApplicationApproved;
use App\Models\User;

final class AuditSellerApplicationApprovedListener
{
    public function handle(SellerApplicationApproved $event): void
    {
        // The actor (the reviewing admin) comes from the request context;
        // the subject is the applicant.
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::SELLER_APPLICATION_APPROVED,
                category: AuditCategory::SELLER_MANAGEMENT,
                severity: AuditSeverity::NOTICE,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->application->user_id,
                description: 'Seller application approved — seller profile created and seller role granted.',
                oldValues: [
                    'status' => 'pending',
                ],
                newValues: [
                    'application_id' => $event->application->id,
                    'store_name' => $event->application->store_name,
                    'status' => $event->application->status->value,
                    'seller_profile_id' => $event->sellerProfile->id,
                    'reviewed_by' => $event->application->reviewed_by,
                ],
                occurredAt: now(),
            ),
        );
    }
}
