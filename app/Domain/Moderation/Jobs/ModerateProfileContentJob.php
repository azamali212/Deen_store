<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Jobs;

use App\Domain\Moderation\Actions\RunProfileModerationCheckAction;
use App\Models\UserProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class ModerateProfileContentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $profileId,
    ) {}

    public function handle(RunProfileModerationCheckAction $action): void
    {
        // Re-fetched fresh here (not passed in from the listener) because this
        // runs later, in the background — we want whatever the profile looks
        // like AT THE TIME the AI actually checks it, not a stale copy from
        // the moment the request came in.
        $profile = UserProfile::find($this->profileId);

        if ($profile === null) {
            Log::info('[Moderation] Profile no longer exists, skipping check.', [
                'profile_id' => $this->profileId,
            ]);

            return;
        }

        $action->execute($profile);
    }
}
