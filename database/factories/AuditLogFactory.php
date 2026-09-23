<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Enums\AuditCategory;
use App\Domain\Audit\Enums\AuditSeverity;
use App\Domain\Audit\Enums\AuditStatus;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
final class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            // Real audit rows almost always have an actor (who did it) — we
            // default to "some user did something", the most common shape.
            'actor_type' => User::class,
            'actor_id' => User::factory(),
            // subject (who/what it happened to) is nullable at the DB level
            // (e.g. a login attempt has no subject), so it defaults empty
            // here — tests that need a subject use the forSubject() state.
            'subject_type' => null,
            'subject_id' => null,
            'action' => AuditAction::PROFILE_UPDATED->value,
            'category' => AuditCategory::USER_MANAGEMENT->value,
            'severity' => AuditSeverity::INFO->value,
            'status' => AuditStatus::SUCCESS->value,
            'description' => fake()->sentence(),
            // Set explicitly rather than relying on the migration's
            // useCurrent() DB default — the same SQLite-default fragility
            // we already hit once with users.status, so we don't lean on
            // it again here.
            'occurred_at' => now()->subMinutes(fake()->numberBetween(1, 1440)),
        ];
    }

    /**
     * Attaches this log row to a given user as its subject — what
     * AuditAiSummaryService::summarizeForUser() filters by.
     */
    public function forSubject(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
    }
}
