<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\LoginFailed;
use Illuminate\Support\Facades\Log;

final class LogFailedLoginListener
{
    public function handle(LoginFailed $event): void
    {
        Log::warning('Login attempt failed.', $event->data->toArray());
    }
}