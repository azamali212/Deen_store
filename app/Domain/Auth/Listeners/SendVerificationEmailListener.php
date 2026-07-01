<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\EmailVerificationRequested;
use App\Domain\Auth\Notifications\VerifyEmailNotification;

final readonly class SendVerificationEmailListener
{
    public function handle(
        EmailVerificationRequested $event
    ): void {
        //dd('Listener Fired');
        $event->user->notify(

            new VerifyEmailNotification(
                $event->token
            )
        );
        //
        //dd('Notification Sent');
    }
}