<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\TwoFactorDisabled;
use App\Domain\Auth\Notifications\TwoFactorDisabledNotification;

final readonly class SendTwoFactorDisabledNotificationListener
{
    public function handle(
        TwoFactorDisabled $event,
    ): void {

        $event->user->notify(
            new TwoFactorDisabledNotification,
        );
    }
}