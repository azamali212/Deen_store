<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Enums\LoginRiskLevel;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Models\User;

final readonly class SuspiciousLoginService
{
    public function __construct(
        private AuthRepositoryInterface $repository,
        private AuthRiskScoringService $riskScoring,
    ) {}

    public function assess(
        User $user,
        string $fingerprint,
        bool $newIp = false,
        bool $newCountry = false,
        int $failedAttempts = 0,
    ): array {

        $trustedDevice = $this->isTrustedDevice(
            $user,
            $fingerprint
        );

        $score = $this->riskScoring->calculate(
            user: $user,
            trustedDevice: $trustedDevice,
            newIp: $newIp,
            newCountry: $newCountry,
            failedAttempts: $failedAttempts,
        );

        $level = $this->riskScoring->level(
            $score
        );

        return [
            'trusted_device' => $trustedDevice,
            'score' => $score,
            'level' => $level,
            'requires_otp' => $this->riskScoring
                ->requiresOtp($score),
            'should_block' => $this->riskScoring
                ->shouldBlock($score),
        ];
    }

    public function isTrustedDevice(
        User $user,
        string $fingerprint
    ): bool {

        return $this->repository
            ->findTrustedDevice(
                $user->id,
                $fingerprint
            ) !== null;
    }

    public function requiresOtp(
        User $user,
        string $fingerprint,
        bool $newIp = false,
        bool $newCountry = false,
        int $failedAttempts = 0,
    ): bool {

        return $this->assess(
            $user,
            $fingerprint,
            $newIp,
            $newCountry,
            $failedAttempts,
        )['requires_otp'];
    }

    public function shouldBlock(
        User $user,
        string $fingerprint,
        bool $newIp = false,
        bool $newCountry = false,
        int $failedAttempts = 0,
    ): bool {

        return $this->assess(
            $user,
            $fingerprint,
            $newIp,
            $newCountry,
            $failedAttempts,
        )['should_block'];
    }

    public function riskLevel(
        User $user,
        string $fingerprint,
        bool $newIp = false,
        bool $newCountry = false,
        int $failedAttempts = 0,
    ): LoginRiskLevel {

        return $this->assess(
            $user,
            $fingerprint,
            $newIp,
            $newCountry,
            $failedAttempts,
        )['level'];
    }
}