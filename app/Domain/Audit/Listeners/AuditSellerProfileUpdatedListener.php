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
use App\Domain\Seller\Events\SellerProfileUpdated;
use App\Models\User;

final class AuditSellerProfileUpdatedListener
{
    public function handle(SellerProfileUpdated $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::SELLER_PROFILE_UPDATED,
                category: AuditCategory::SELLER_MANAGEMENT,
                severity: AuditSeverity::INFO,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->profile->user_id,
                description: 'Seller business profile updated.',
                newValues: [
                    'seller_profile_id' => $event->profile->id,
                    'store_name' => $event->profile->store_name,
                    'changed_fields' => $event->changedFields,
                ],
                occurredAt: now(),
            ),
        );
    }
}
