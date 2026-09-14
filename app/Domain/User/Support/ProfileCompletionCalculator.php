<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Models\UserProfile;

/**
 * Pure calculation, no I/O — decides what percentage of a profile is
 * "complete" based on which optional fields are filled in. Kept out of the
 * Service layer so the scoring rules stay unit-testable in isolation and
 * reusable from anywhere (a queued job, an Action) without a repository.
 */
final readonly class ProfileCompletionCalculator
{
    /**
     * @var array<string, int> field => weight (weights must total 100)
     */
    private const WEIGHTS = [
        'avatar_path' => 20,
        'date_of_birth' => 15,
        'gender' => 10,
        'bio' => 20,
        'website_url' => 10,
        'occupation' => 15,
        'company_name' => 10,
    ];

    public function calculate(UserProfile $profile): int
    {
        $score = 0;

        foreach (self::WEIGHTS as $field => $weight) {
            if (filled($profile->{$field})) {
                $score += $weight;
            }
        }

        return $score;
    }
}
