<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\DTO\ModerationCheckResultDTO;
use App\Domain\Moderation\Enums\ModerationStatus;
use App\Domain\Moderation\Events\ProfileContentBlocked;
use App\Domain\Moderation\Exceptions\ProfileContentRejectedException;
use App\Domain\Moderation\Services\ProfileModerationAiService;
use App\Models\ModerationFlag;

final readonly class EnforceContentModerationAction
{
    public function __construct(
        private ProfileModerationAiService $aiService,
    ) {}

    /**
     * Blocks a profile-field update. Throws before anything is saved if the
     * AI flags any field — nothing partial ever reaches the database.
     *
     * @param  array<string, string|null>  $textFields
     */
    public function enforceText(int $userId, array $textFields): void
    {
        $result = $this->aiService->checkProfileText($textFields);

        $this->rejectIfFlagged($userId, $result, snapshot: $textFields);
    }

    /**
     * Blocks an avatar upload. Called on the raw uploaded bytes, before the
     * file is ever written to storage — a rejected image never touches disk.
     */
    public function enforceAvatar(int $userId, string $bytes, string $mimeType): void
    {
        $result = $this->aiService->checkAvatarImage($bytes, $mimeType);

        $this->rejectIfFlagged($userId, $result, snapshot: ['avatar' => 'uploaded image, mime='.$mimeType]);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function rejectIfFlagged(int $userId, ModerationCheckResultDTO $result, array $snapshot): void
    {
        if ($result->isClean) {
            return;
        }

        // status = REJECTED with no reviewed_by/reviewed_at is how we tell
        // "the AI auto-rejected this at the door" apart from "an admin
        // reviewed a pending flag and rejected it" — same column, no schema
        // change needed, and the admin flags list still shows both.
        $flag = ModerationFlag::create([
            'user_id' => $userId,
            'status' => ModerationStatus::REJECTED->value,
            'severity' => $result->severity?->value,
            'flagged_fields' => $result->flaggedFields,
            'ai_summary' => $result->summary,
            'snapshot' => $snapshot,
        ]);

        event(new ProfileContentBlocked($flag));

        throw ProfileContentRejectedException::forResult($result);
    }
}
