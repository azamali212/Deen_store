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
use App\Domain\Seller\Events\SellerStoreReactivated;
use App\Domain\Seller\Events\SellerStoreSuspended;
use App\Models\User;

/**
 * One handler for both store-status events (same shape as
 * AuditAddressChangeListener).
 */
final class AuditSellerStoreStatusListener
{
    public function handle(SellerStoreSuspended|SellerStoreReactivated $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        $suspended = $event instanceof SellerStoreSuspended;

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: $suspended ? AuditAction::SELLER_SUSPENDED : AuditAction::SELLER_REACTIVATED,
                category: AuditCategory::SELLER_MANAGEMENT,
                severity: $suspended ? AuditSeverity::WARNING : AuditSeverity::NOTICE,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->profile->user_id,
                description: $suspended
                    ? 'Seller store suspended by an admin.'
                    : 'Seller store reactivated by an admin.',
                newValues: array_filter([
                    'seller_profile_id' => $event->profile->id,
                    'store_name' => $event->profile->store_name,
                    'status' => $event->profile->status->value,
                    'reason' => $suspended ? $event->reason : null,
                    'suspended_by' => $event->profile->suspended_by,
                ], static fn (mixed $value): bool => $value !== null),
                occurredAt: now(),
            ),
        );
    }
}
