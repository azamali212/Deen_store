<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\OtpSent;
use App\Domain\Auth\Notifications\LoginOtpNotification;
use App\Models\User;

final class SendOtpNotificationListener
{
    public function handle(OtpSent $event): void
    {
        $user = User::find($event->data->userId);

        if (! $user) {
            return;
        }

        $user->notify(
            new LoginOtpNotification(
                otp: $event->data->code,
                purpose: $event->data->purpose->value,
                identifier: $event->data->identifier,
            )
        );
    }
}