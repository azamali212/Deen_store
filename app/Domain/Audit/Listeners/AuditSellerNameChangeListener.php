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
use App\Domain\Seller\Events\SellerStoreNameChangeRequested;
use App\Domain\Seller\Events\SellerStoreNameChangeReviewed;
use App\Models\SellerProfile;

/**
 * A rename breaks the link between the name customers see and the name on
 * the verified documents, so BOTH names go in the record every time.
 */
final class AuditSellerNameChangeListener
{
    public function handle(SellerStoreNameChangeRequested|SellerStoreNameChangeReviewed $event): void
    {
        $context = app()->runningInConsole()
            ? AuditContextDTO::system()
            : AuditContextDTO::fromRequest(request());

        $profile = $event->profile;
        $requested = $event instanceof SellerStoreNameChangeRequested;

        [$action, $severity, $description, $values] = $requested
            ? [
                AuditAction::SELLER_STORE_NAME_CHANGE_REQUESTED,
                AuditSeverity::INFO,
                'A seller asked to rename their store.',
                [
                    'current_name' => $profile->store_name,
                    'requested_name' => $profile->pending_store_name,
                ],
            ]
            : [
                $event->approved
                    ? AuditAction::SELLER_STORE_RENAMED
                    : AuditAction::SELLER_STORE_NAME_CHANGE_REJECTED,
                AuditSeverity::NOTICE,
                $event->approved
                    ? 'An admin approved a store rename.'
                    : 'An admin rejected a store rename.',
                [
                    'previous_name' => $event->previousName,
                    'current_name' => $profile->store_name,
                    'reason' => $event->reason,
                ],
            ];

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: $action,
                category: AuditCategory::SELLER_MANAGEMENT,
                severity: $severity,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: SellerProfile::class,
                subjectId: (string) $profile->id,
                description: $description,
                newValues: array_filter(
                    $values + ['seller_profile_id' => $profile->id],
                    static fn (mixed $v): bool => $v !== null,
                ),
                occurredAt: now(),
            ),
        );
    }
}
