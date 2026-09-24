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
use App\Domain\Seller\Events\SellerBankProofReviewed;
use App\Domain\Seller\Events\SellerBankProofUploaded;
use App\Models\User;
use LogicException;

/**
 * P6-2 audit trail. Like every other bank audit row, it records only the
 * last 4 digits — never the account number, not even encrypted.
 */
final class AuditSellerBankProofListener
{
    public function handle(SellerBankProofUploaded|SellerBankProofReviewed $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        [$action, $severity, $description, $extra] = match (true) {
            $event instanceof SellerBankProofUploaded => [
                AuditAction::SELLER_BANK_PROOF_UPLOADED,
                AuditSeverity::INFO,
                $event->autoMatched
                    ? 'Bank statement uploaded and automatically matched to the payout account.'
                    : 'Bank statement uploaded for the payout account — awaiting admin review.',
                ['auto_matched' => $event->autoMatched],
            ],
            $event instanceof SellerBankProofReviewed => [
                $event->verified
                    ? AuditAction::SELLER_BANK_VERIFIED_BY_ADMIN
                    : AuditAction::SELLER_BANK_PROOF_REJECTED,
                AuditSeverity::NOTICE,
                $event->verified
                    ? 'Payout bank account verified by an admin.'
                    : 'Bank statement rejected by an admin.',
                array_filter([
                    'reviewed_by' => $event->profile->bank_verified_by,
                    'reason' => $event->reason,
                ], static fn (mixed $value): bool => $value !== null),
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
                subjectType: User::class,
                subjectId: (string) $event->profile->user_id,
                description: $description,
                newValues: [
                    'seller_profile_id' => $event->profile->id,
                    'account_last4' => $event->profile->bank_account_last4,
                    'bank_verification_status' => $event->profile->bank_verification_status?->value,
                ] + $extra,
                occurredAt: now(),
            ),
        );
    }
}
