<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Events\ProfileCompleted;
use App\Domain\User\Notifications\ProfileCompletedNotification;

final readonly class SendProfileCompletedNotificationListener
{
    public function handle(ProfileCompleted $event): void
    {
        $event->profile->user->notify(
            new ProfileCompletedNotification,
        );
    }
}
