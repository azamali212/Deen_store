<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\RecoveryCodesRegenerated;
use App\Domain\Auth\Notifications\RecoveryCodesNotification;

final readonly class SendRecoveryCodesRegeneratedNotificationListener
{
    public function handle(
        RecoveryCodesRegenerated $event,
    ): void {

        $event->user->notify(
            new RecoveryCodesNotification(
                $event->codes,
            ),
        );
    }
}