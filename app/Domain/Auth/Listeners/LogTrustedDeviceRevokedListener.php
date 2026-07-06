<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\TrustedDeviceRevoked;
use App\Domain\Auth\Notifications\TrustedDeviceRevokedNotification;
use Illuminate\Support\Facades\Log;

final readonly class LogTrustedDeviceRevokedListener
{
    public function handle(
        TrustedDeviceRevoked $event,
    ): void {

        Log::info(
            'Trusted device revoked.',
            [

                'user_id' => $event->device->user_id,

                'fingerprint' => $event->device->fingerprint,

            ]
        );

        $event->device
            ->user
            ->notify(

                new TrustedDeviceRevokedNotification(

                    $event->device->device_name,

                    $event->device->browser,

                    $event->device->operating_system,

                )

            );
    }
}