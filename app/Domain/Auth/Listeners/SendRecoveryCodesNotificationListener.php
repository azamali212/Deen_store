<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\RecoveryCodesGenerated;
use App\Domain\Auth\Notifications\RecoveryCodesNotification;

final readonly class SendRecoveryCodesNotificationListener
{
    public function handle(
        RecoveryCodesGenerated $event,
    ): void {

        $event->user->notify(
            new RecoveryCodesNotification(
                $event->codes,
            ),
        );
    }
}