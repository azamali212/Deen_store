<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\TwoFactorEnabled;
use App\Domain\Auth\Notifications\TwoFactorEnabledNotification;

final readonly class SendTwoFactorEnabledNotificationListener
{
    public function handle(
        TwoFactorEnabled $event,
    ): void {

        $event->user->notify(
            new TwoFactorEnabledNotification,
        );
    }
}