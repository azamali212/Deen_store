<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\AccountUnlocked;
use Illuminate\Support\Facades\Log;

final readonly class LogAccountUnlockedListener
{
    public function handle(AccountUnlocked $event): void
    {
        Log::info('User account unlocked.', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'unlocked_by_user_id' => $event->unlockedByUserId,
            'reason' => $event->reason,
            'unlocked_at' => now(),
        ]);
    }
}