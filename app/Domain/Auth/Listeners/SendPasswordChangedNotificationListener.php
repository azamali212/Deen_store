<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\PasswordChanged;
use App\Domain\Auth\Notifications\PasswordChangedNotification;

final readonly class SendPasswordChangedNotificationListener
{
    public function handle(
        PasswordChanged $event
    ): void {

        $event->user->notify(

            new PasswordChangedNotification()

        );
    }
}