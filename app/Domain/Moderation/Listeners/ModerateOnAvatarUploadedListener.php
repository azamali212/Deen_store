<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Listeners;

use App\Domain\Moderation\Jobs\ModerateProfileContentJob;
use App\Domain\User\Events\AvatarUploaded;

final class ModerateOnAvatarUploadedListener
{
    public function handle(AvatarUploaded $event): void
    {
        ModerateProfileContentJob::dispatch($event->profile->id);
    }
}
