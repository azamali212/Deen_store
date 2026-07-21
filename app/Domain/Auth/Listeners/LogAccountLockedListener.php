<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\AccountLocked;
use Illuminate\Support\Facades\Log;

final readonly class LogAccountLockedListener
{
    public function handle(AccountLocked $event): void
    {
        Log::warning('User account locked.', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'reason' => $event->reason,
            'locked_until' => $event->lockedUntil,
            'locked_at' => now(),
        ]);
    }
}