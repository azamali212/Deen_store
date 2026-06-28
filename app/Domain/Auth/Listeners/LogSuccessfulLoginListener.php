<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\UserLoggedIn;
use Illuminate\Support\Facades\Log;

final class LogSuccessfulLoginListener
{
    public function handle(UserLoggedIn $event): void
    {
        Log::info('User logged in successfully.', $event->data->toArray());
    }
}