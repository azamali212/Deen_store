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
use App\Domain\Seller\Events\SellerBankUnverified;
use App\Models\User;

final class AuditSellerBankUnverifiedListener
{
    public function handle(SellerBankUnverified $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::SELLER_BANK_UNVERIFIED,
                category: AuditCategory::SELLER_MANAGEMENT,
                severity: AuditSeverity::WARNING,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->profile->user_id,
                description: 'Payout bank account does not match the verified bank statement.',
                newValues: [
                    'seller_profile_id' => $event->profile->id,
                    'account_last4' => $event->enteredLast4,
                    'bank_verification_status' => 'mismatch',
                ],
                occurredAt: now(),
            ),
        );
    }
}
