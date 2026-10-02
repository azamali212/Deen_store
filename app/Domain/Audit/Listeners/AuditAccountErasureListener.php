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
use App\Domain\User\Events\AccountErased;
use App\Domain\User\Events\AccountErasureCancelled;
use App\Domain\User\Events\AccountErasureRequested;
use App\Models\User;
use LogicException;

/**
 * C64 — the one audit listener that must be careful what it writes.
 *
 * Everywhere else in this codebase an audit row records the old and new
 * values, because that is what makes a trail useful. Here it must not. An
 * audit row reading "erased john@example.com, +44 7700 900123" would put the
 * personal data straight back into permanent storage and quietly undo the
 * erasure it was recording.
 *
 * So the completed-erasure row carries the user id, the uuid and the date,
 * and nothing else. That is enough to answer "did we honour the request, and
 * when?" — which is the only question the trail needs to answer.
 */
final class AuditAccountErasureListener
{
    public function handle(
        AccountErasureRequested|AccountErasureCancelled|AccountErased $event,
    ): void {

        $context = app()->runningInConsole()
            ? AuditContextDTO::system()
            : AuditContextDTO::fromRequest(request());

        [$action, $severity, $description, $subjectId, $values] = match (true) {

            $event instanceof AccountErasureRequested => [
                AuditAction::USER_ERASURE_REQUESTED,
                AuditSeverity::WARNING,
                'A user asked for their account to be deleted.',
                (string) $event->user->id,
                [
                    'requested_at' => $event->user->erasure_requested_at?->toIso8601String(),
                    'scheduled_for' => $event->scheduledFor->toIso8601String(),
                ],
            ],

            $event instanceof AccountErasureCancelled => [
                AuditAction::USER_ERASURE_CANCELLED,
                AuditSeverity::NOTICE,
                $event->byUser
                    ? 'A user cancelled their own deletion request.'
                    : 'A deletion request was cancelled by the system because it could no longer be honoured.',
                (string) $event->user->id,
                array_filter([
                    'cancelled_by' => $event->byUser ? 'user' : 'system',
                    'reason' => $event->reason,
                ], static fn (mixed $value): bool => $value !== null),
            ],

            $event instanceof AccountErased => [
                AuditAction::USER_ERASED,
                AuditSeverity::CRITICAL,
                'An account was erased. Identifying data removed; the record of the request is kept.',
                (string) $event->userId,
                [
                    'uuid' => $event->uuid,
                    'erased_at' => now()->toIso8601String(),
                ],
            ],

            default => throw new LogicException('Unsupported event: '.$event::class),
        };

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: $action,
                category: AuditCategory::USER_MANAGEMENT,
                severity: $severity,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: $subjectId,
                description: $description,
                newValues: $values,
                occurredAt: now(),
            ),
        );
    }
}
