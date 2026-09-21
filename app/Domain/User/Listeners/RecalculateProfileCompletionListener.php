<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Events\ProfileCompleted;
use App\Domain\User\Services\ProfileCompletionService;
use App\Models\UserProfile;

/**
 * Shared handler for ProfileUpdated / AvatarUploaded / AvatarDeleted — all
 * three carry a `profile` property, so one listener recalculates the
 * completion % after any of them. When the new score first hits 100%, it
 * fires ProfileCompleted so SendProfileCompletedNotificationListener can
 * congratulate the user — decoupled, instead of this listener knowing
 * about notifications directly.
 */
final readonly class RecalculateProfileCompletionListener
{
    public function __construct(
        private ProfileCompletionService $completionService,
    ) {}

    public function handle(object $event): void
    {
        if (! property_exists($event, 'profile')) {
            return;
        }

        /** @var UserProfile $profile */
        $profile = $event->profile;
        $previousPercentage = $profile->profile_completion;

        $updated = $this->completionService->recalculate($profile->user_id);

        if ($updated->profile_completion === 100 && $previousPercentage !== 100) {
            event(new ProfileCompleted($updated, 100));
        }
    }
}
