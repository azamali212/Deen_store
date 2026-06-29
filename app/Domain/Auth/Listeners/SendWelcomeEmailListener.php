<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\UserCreated;
use App\Domain\Auth\Notifications\WelcomeUserNotification;

final readonly class SendWelcomeEmailListener
{
    public function handle(
        UserCreated $event
    ): void {

        $event->user->notify(

            new WelcomeUserNotification(
                $event->createdBy
            )

        );
    }
}