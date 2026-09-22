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
use App\Domain\Auth\Events\UserLoggedOut;
use App\Models\User;

final class AuditUserLoggedOutListener
{
    public function handle(UserLoggedOut $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request(), panel: $event->data->panel->value)
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::LOGOUT,
                category: AuditCategory::AUTHENTICATION,
                severity: AuditSeverity::INFO,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: $event->data->userId,
                description: $event->data->logoutAllDevices
                    ? 'User logged out of all devices.'
                    : 'User logged out.',
                metadata: [
                    'email' => $event->data->email,
                    'session_id' => $event->data->sessionId,
                    'logout_all_devices' => $event->data->logoutAllDevices,
                ],
                occurredAt: $event->data->occurredAt,
            ),
        );
    }
}
