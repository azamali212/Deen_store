<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Enums\LoginRiskLevel;
use App\Models\User;

final readonly class AuthRiskScoringService
{
    public function calculate(
        User $user,
        bool $trustedDevice,
        bool $newIp,
        bool $newCountry,
        int $failedAttempts = 0
    ): int {
        $score = 0;

        if (! $trustedDevice) {
            $score += 30;
        }

        if ($newIp) {
            $score += 20;
        }

        if ($newCountry) {
            $score += 30;
        }

        if ($failedAttempts > 3) {
            $score += 25;
        }

        if ($user->hasRole('super_admin')) {
            $score += 20;
        }

        return min($score, 100);
    }

    public function level(
        int $score
    ): LoginRiskLevel {
        return match (true) {

            $score >= 70
                => LoginRiskLevel::HIGH,

            $score >= 31
                => LoginRiskLevel::MEDIUM,

            default
                => LoginRiskLevel::LOW,
        };
    }

    public function requiresOtp(
        int $score
    ): bool {
        return $score >= 31;
    }

    public function shouldBlock(
        int $score
    ): bool {
        return $score >= 90;
    }
}