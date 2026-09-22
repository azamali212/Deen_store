<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Events\ProfileFlaggedForReview;
use App\Domain\Moderation\Services\ProfileModerationAiService;
use App\Models\ModerationFlag;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Log;

final readonly class RunProfileModerationCheckAction
{
    public function __construct(
        private ProfileModerationAiService $aiService,
    ) {}

    public function execute(UserProfile $profile): ?ModerationFlag
    {
        $textFields = [
            'username' => $profile->username,
            'bio' => $profile->bio,
            'website_url' => $profile->website_url,
            'occupation' => $profile->occupation,
            'company_name' => $profile->company_name,
        ];

        $result = $this->aiService->check($textFields, $profile->avatar_path);

        if ($result->isClean) {
            Log::info('[Moderation] Profile check passed clean.', ['user_id' => $profile->user_id]);

            return null;
        }

        $flag = ModerationFlag::create([
            'user_id' => $profile->user_id,
            'status' => 'pending',
            'severity' => $result->severity?->value,
            'flagged_fields' => $result->flaggedFields,
            'ai_summary' => $result->summary,
            'snapshot' => [...$textFields, 'avatar_path' => $profile->avatar_path],
        ]);

        event(new ProfileFlaggedForReview($flag));

        return $flag;
    }
}
