<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Events\AccountErasureCancelled;
use App\Domain\User\Events\AccountErasureRequested;
use App\Domain\User\Notifications\AccountErasureCancelledNotification;
use App\Domain\User\Notifications\AccountErasureScheduledNotification;

/**
 * Deliberately has no AccountErased branch. There is nobody left to write to.
 */
final class NotifyUserOfErasureListener
{
    public function handle(AccountErasureRequested|AccountErasureCancelled $event): void
    {
        if ($event instanceof AccountErasureRequested) {
            $event->user->notify(
                new AccountErasureScheduledNotification($event->scheduledFor),
            );

            return;
        }

        $event->user->notify(
            new AccountErasureCancelledNotification($event->byUser, $event->reason),
        );
    }
}
