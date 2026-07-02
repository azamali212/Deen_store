<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\PasswordResetRequested;
use App\Domain\Auth\Notifications\ResetPasswordNotification;

final readonly class SendPasswordResetEmailListener
{
    public function handle(
        PasswordResetRequested $event
    ): void {

        $event->user->notify(

            new ResetPasswordNotification(
                $event->token
            )

        );
    }
}