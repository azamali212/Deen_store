<?php

declare(strict_types=1);

namespace App\Domain\Audit\Listeners;

use App\Domain\Audit\Actions\CreateAuditLogAction;
use App\Domain\Audit\DTO\AuditContextDTO;
use App\Domain\Audit\DTO\CreateAuditLogDTO;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Enums\AuditCategory;
use App\Domain\Audit\Enums\AuditSeverity;
use App\Domain\Audit\Enums\AuditStatus;
use App\Domain\Auth\Events\TwoFactorDisabled;
use App\Models\User;

final readonly class AuditTwoFactorDisabledListener
{
    public function __construct(
        private CreateAuditLogAction $action,
    ) {}

    public function handle(
        TwoFactorDisabled $event,
    ): void {

        $context = app()->runningInConsole()
            ? AuditContextDTO::system()
            : AuditContextDTO::fromRequest(
                request(),
            );

        $this->action->execute(
            new CreateAuditLogDTO(
                action: AuditAction::TWO_FACTOR_DISABLED,
                category: AuditCategory::SECURITY,
                severity: AuditSeverity::NOTICE,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->user->id,
                description: 'Two-factor authentication disabled.',
                metadata: [
                    'email' => $event->user->email,
                ],
                occurredAt: now(),
            ),
        );
    }
}