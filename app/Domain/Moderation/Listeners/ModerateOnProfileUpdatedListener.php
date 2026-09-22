<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Listeners;

use App\Domain\Moderation\Jobs\ModerateProfileContentJob;
use App\Domain\User\Events\ProfileUpdated;

final class ModerateOnProfileUpdatedListener
{
    public function handle(ProfileUpdated $event): void
    {
        ModerateProfileContentJob::dispatch($event->profile->id);
    }
}
