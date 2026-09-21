<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use Illuminate\Support\Facades\Log;

/**
 * Shared audit-log handler for all four address events. AddressDeleted
 * carries userId/addressId (the model is already gone); the other three
 * carry the `address` model itself — handled generically so one listener
 * covers all of them, mirroring Auth domain's LogSessionTerminatedListener.
 */
final readonly class LogAddressChangeListener
{
    public function handle(object $event): void
    {
        $context = property_exists($event, 'address')
            ? [
                'user_id' => $event->address->user_id,
                'address_id' => $event->address->id,
            ]
            : [
                'user_id' => $event->userId ?? null,
                'address_id' => $event->addressId ?? null,
            ];

        Log::info('[User][Address] '.$event::class, $context);
    }
}
