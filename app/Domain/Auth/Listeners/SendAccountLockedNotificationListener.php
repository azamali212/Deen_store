<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\AccountLocked;
use App\Domain\Auth\Notifications\AccountLockedNotification;

final readonly class SendAccountLockedNotificationListener
{
    public function handle(AccountLocked $event): void
    {
        $event->user->notify(
            new AccountLockedNotification(
                reason: $event->reason,
                lockedUntil: $event->lockedUntil,
            ),
        );
    }
}