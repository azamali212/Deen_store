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
use App\Domain\Seller\Events\SellerBankDetailsChanged;
use App\Models\User;

final class AuditSellerBankDetailsChangedListener
{
    public function handle(SellerBankDetailsChanged $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::SELLER_BANK_DETAILS_CHANGED,
                category: AuditCategory::SELLER_MANAGEMENT,
                // Replacing an existing payout account is the fraud pattern
                // -> WARNING. Adding the first one is expected -> NOTICE.
                severity: $event->isFirstTime ? AuditSeverity::NOTICE : AuditSeverity::WARNING,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->profile->user_id,
                description: $event->isFirstTime
                    ? 'Seller payout bank account added.'
                    : 'Seller payout bank account CHANGED.',
                // Last-4 + bank name only — never the account number, not
                // even encrypted (BLUEPRINT section 6).
                oldValues: [
                    'bank_name' => $event->oldBankName,
                    'account_last4' => $event->oldLast4,
                ],
                newValues: [
                    'seller_profile_id' => $event->profile->id,
                    'bank_name' => $event->newBankName,
                    'account_last4' => $event->newLast4,
                ],
                occurredAt: now(),
            ),
        );
    }
}
