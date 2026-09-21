<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Events\UserActivated;
use App\Domain\User\Notifications\AccountActivatedNotification;

final readonly class SendAccountActivatedNotificationListener
{
    public function handle(UserActivated $event): void
    {
        $event->user->notify(
            new AccountActivatedNotification,
        );
    }
}
