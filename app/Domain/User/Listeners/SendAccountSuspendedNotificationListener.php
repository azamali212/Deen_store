<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Events\UserSuspended;
use App\Domain\User\Notifications\AccountSuspendedNotification;

final readonly class SendAccountSuspendedNotificationListener
{
    public function handle(UserSuspended $event): void
    {
        $event->user->notify(
            new AccountSuspendedNotification($event->reason),
        );
    }
}
