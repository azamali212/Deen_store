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
use App\Domain\User\Events\PhoneVerified;
use App\Models\User;

final class AuditPhoneVerifiedListener
{
    public function handle(PhoneVerified $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::PHONE_VERIFIED,
                category: AuditCategory::USER_MANAGEMENT,
                severity: AuditSeverity::NOTICE,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->user->id,
                description: 'User verified a phone number.',
                newValues: [
                    'phone' => $event->user->phone,
                    'phone_verified_at' => $event->user->phone_verified_at?->toISOString(),
                ],
                occurredAt: now(),
            ),
        );
    }
}
