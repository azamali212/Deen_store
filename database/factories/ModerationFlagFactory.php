<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Moderation\Enums\ModerationSeverity;
use App\Domain\Moderation\Enums\ModerationStatus;
use App\Models\ModerationFlag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModerationFlag>
 */
final class ModerationFlagFactory extends Factory
{
    protected $model = ModerationFlag::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => ModerationStatus::PENDING,
            'severity' => ModerationSeverity::MEDIUM,
            'flagged_fields' => [
                'bio' => ['category' => 'profanity_and_sexual_content', 'reason' => 'Test reason.'],
            ],
            'ai_summary' => 'Flagged bio contains explicit language.',
            'snapshot' => ['bio' => 'some flagged bio text'],
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_notes' => null,
        ];
    }

    /**
     * Indicate that an admin already reviewed this flag.
     */
    public function resolved(ModerationStatus $status = ModerationStatus::APPROVED): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'review_notes' => 'Reviewed in test.',
        ]);
    }
}
