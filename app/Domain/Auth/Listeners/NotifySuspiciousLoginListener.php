<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\SuspiciousLoginDetected;
use App\Domain\Auth\Notifications\SuspiciousLoginNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class NotifySuspiciousLoginListener
{
    public function handle(SuspiciousLoginDetected $event): void
    {
        Log::warning('Suspicious login detected.', $event->data->toArray());

        $user = User::query()
            ->find($event->data->userId);

        if (! $user) {
            return;
        }

        $user->notify(
            new SuspiciousLoginNotification(
                data: $event->data
            )
        );
    }
}