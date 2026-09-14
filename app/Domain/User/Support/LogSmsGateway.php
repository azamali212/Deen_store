<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Contracts\SmsGatewayInterface;
use Illuminate\Support\Facades\Log;

/**
 * Development-only SMS gateway.
 *
 * Sends nothing over the network — writes the message to the Laravel log
 * instead, so you can read the OTP code straight from storage/logs/laravel.log
 * (or `php artisan pail`) while building/testing, with zero third-party
 * account or cost. Swap the AppServiceProvider binding to TwilioSmsGateway
 * (or any other SmsGatewayInterface implementation) when you're ready to
 * send real SMS — nothing else in the codebase changes.
 */
final readonly class LogSmsGateway implements SmsGatewayInterface
{
    public function send(string $toPhoneNumber, string $message): void
    {
        Log::info("[LogSmsGateway] SMS to {$toPhoneNumber}: {$message}");
    }
}
