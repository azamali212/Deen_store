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
use App\Domain\Seller\Events\SellerStoreClosed;
use App\Domain\Seller\Events\SellerStoreReopenRequested;
use App\Domain\Seller\Events\SellerStoreReopened;
use App\Models\SellerProfile;
use LogicException;

/**
 * P8-5 — closure is audited SEPARATELY from suspension. Reading this trail
 * back later, "the owner closed it" and "we suspended them" must never be
 * confusable; that is the whole reason `closed` is its own status.
 */
final class AuditSellerClosureListener
{
    public function handle(
        SellerStoreClosed|SellerStoreReopenRequested|SellerStoreReopened $event,
    ): void {
        $context = app()->runningInConsole()
            ? AuditContextDTO::system()
            : AuditContextDTO::fromRequest(request());

        $profile = $event->profile;

        [$action, $severity, $description] = match (true) {
            $event instanceof SellerStoreClosed => [
                AuditAction::SELLER_STORE_CLOSED,
                AuditSeverity::NOTICE,
                'A seller closed their own store.',
            ],
            $event instanceof SellerStoreReopenRequested => [
                AuditAction::SELLER_STORE_REOPEN_REQUESTED,
                AuditSeverity::INFO,
                'A seller asked for their closed store to be reopened.',
            ],
            $event instanceof SellerStoreReopened => [
                AuditAction::SELLER_STORE_REOPENED,
                AuditSeverity::NOTICE,
                'An admin reopened a closed store.',
            ],
            default => throw new LogicException('Unsupported event: '.$event::class),
        };

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
                newValues: array_filter([
                    'seller_profile_id' => $profile->id,
                    'store_name' => $profile->store_name,
                    'status' => $profile->status->value,
                    'closure_reason' => $profile->closure_reason,
                ], static fn (mixed $value): bool => $value !== null),
                occurredAt: now(),
            ),
        );
    }
}
