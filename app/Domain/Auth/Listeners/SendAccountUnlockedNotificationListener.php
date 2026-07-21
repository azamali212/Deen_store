<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\AccountUnlocked;
use App\Domain\Auth\Notifications\AccountUnlockedNotification;

final readonly class SendAccountUnlockedNotificationListener
{
    public function handle(AccountUnlocked $event): void
    {
        $event->user->notify(
            new AccountUnlockedNotification(
                reason: $event->reason,
            ),
        );
    }
}