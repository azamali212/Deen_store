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
use App\Domain\Seller\Enums\SellerKycStatus;
use App\Domain\Seller\Events\SellerDocumentRenewalReviewed;
use App\Domain\Seller\Events\SellerDocumentRenewalUploaded;
use App\Domain\Seller\Events\SellerKycStatusChanged;
use App\Models\SellerProfile;
use LogicException;

/**
 * Phase 8a audit. Expiry DATES are safe to record; the identity numbers
 * behind them are not, and never appear here.
 */
final class AuditSellerKycListener
{
    public function handle(
        SellerDocumentRenewalUploaded|SellerDocumentRenewalReviewed|SellerKycStatusChanged $event,
    ): void {
        // The nightly command runs with no request behind it.
        $context = app()->runningInConsole()
            ? AuditContextDTO::system()
            : AuditContextDTO::fromRequest(request());

        [$profile, $action, $severity, $description, $values] = match (true) {
            $event instanceof SellerDocumentRenewalUploaded => [
                $event->renewal->sellerProfile,
                AuditAction::SELLER_DOCUMENT_RENEWAL_UPLOADED,
                AuditSeverity::INFO,
                'A seller uploaded a replacement identity document.',
                [
                    'renewal_id' => $event->renewal->id,
                    'document_type' => $event->renewal->document_type->value,
                    'ai_status' => $event->renewal->ai_status?->value,
                ],
            ],
            $event instanceof SellerDocumentRenewalReviewed => [
                $event->renewal->sellerProfile,
                $event->approved
                    ? AuditAction::SELLER_DOCUMENT_RENEWAL_APPROVED
                    : AuditAction::SELLER_DOCUMENT_RENEWAL_REJECTED,
                AuditSeverity::NOTICE,
                $event->approved
                    ? 'An admin accepted a replacement identity document.'
                    : 'An admin rejected a replacement identity document.',
                [
                    'renewal_id' => $event->renewal->id,
                    'document_type' => $event->renewal->document_type->value,
                    'reviewed_by' => $event->renewal->reviewed_by,
                    'new_expiry' => $event->renewal->extracted_expiry_date?->toDateString(),
                ],
            ],
            $event instanceof SellerKycStatusChanged => [
                $event->profile,
                $event->current === SellerKycStatus::EXPIRED
                    ? AuditAction::SELLER_KYC_EXPIRED
                    : AuditAction::SELLER_KYC_EXPIRING,
                // Expiry freezes money, so it is a WARNING; the 30-day
                // heads-up is not.
                $event->current === SellerKycStatus::EXPIRED
                    ? AuditSeverity::WARNING
                    : AuditSeverity::INFO,
                'A seller\'s document validity changed to '.$event->current->value.'.',
                [
                    'previous_status' => $event->previous->value,
                    'kyc_status' => $event->current->value,
                    'expires_on' => $event->profile->earliestKycExpiry()?->toDateString(),
                ],
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
                newValues: $values + ['seller_profile_id' => $profile->id],
            ),
        );
    }
}
