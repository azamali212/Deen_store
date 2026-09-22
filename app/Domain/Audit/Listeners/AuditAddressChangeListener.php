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
use App\Domain\User\Events\AddressAdded;
use App\Domain\User\Events\AddressDeleted;
use App\Domain\User\Events\AddressUpdated;
use App\Domain\User\Events\DefaultAddressChanged;
use App\Models\User;

/**
 * Shared real Audit-domain handler for all four address events — mirrors
 * User\Listeners\LogAddressChangeListener (which only writes to laravel.log,
 * not the audit_logs table) but produces a queryable audit record.
 * AddressDeleted carries userId/addressId only (the model is already gone
 * by the time it fires); the other three carry the address model itself.
 */
final class AuditAddressChangeListener
{
    public function handle(object $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        [$action, $description, $subjectId, $newValues] = match ($event::class) {
            AddressAdded::class => [
                AuditAction::ADDRESS_ADDED,
                'A new address was added.',
                $event->address->user_id,
                [
                    'address_id' => $event->address->id,
                    'type' => $event->address->type?->value,
                    'city' => $event->address->city,
                    'is_default' => $event->address->is_default,
                ],
            ],
            AddressUpdated::class => [
                AuditAction::ADDRESS_UPDATED,
                'An address was updated.',
                $event->address->user_id,
                [
                    'address_id' => $event->address->id,
                    'type' => $event->address->type?->value,
                    'city' => $event->address->city,
                ],
            ],
            DefaultAddressChanged::class => [
                AuditAction::ADDRESS_DEFAULT_CHANGED,
                'Default address was changed.',
                $event->address->user_id,
                [
                    'address_id' => $event->address->id,
                ],
            ],
            AddressDeleted::class => [
                AuditAction::ADDRESS_DELETED,
                'An address was deleted.',
                $event->userId,
                [
                    'address_id' => $event->addressId,
                ],
            ],
            default => throw new \LogicException(
                'AuditAddressChangeListener received an unsupported event: '.$event::class,
            ),
        };

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: $action,
                category: AuditCategory::USER_MANAGEMENT,
                severity: AuditSeverity::INFO,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $subjectId,
                description: $description,
                newValues: $newValues,
                occurredAt: now(),
            ),
        );
    }
}
