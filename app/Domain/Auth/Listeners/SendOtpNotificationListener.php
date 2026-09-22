<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Enums\OtpPurpose;
use App\Domain\Auth\Events\OtpSent;
use App\Domain\Auth\Notifications\LoginOtpNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class SendOtpNotificationListener
{
    public function handle(OtpSent $event): void
    {
        $user = User::find($event->data->userId);

        if (! $user) {
            return;
        }

        if ($event->data->purpose === OtpPurpose::PHONE_VERIFICATION) {
            // No SMS provider wired up yet (dev/log-based, by design — see
            // Phone verification decision). Logged instead of sent so the
            // flow is fully testable until a real gateway is plugged in.
            Log::info('[Dev][Phone] Verification OTP (no SMS provider configured).', [
                'user_id' => $event->data->userId,
                'phone' => $event->data->identifier,
                'otp' => $event->data->code,
            ]);

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
