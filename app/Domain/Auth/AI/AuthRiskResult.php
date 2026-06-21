<?php

declare(strict_types=1);

namespace App\Domain\Auth\AI;

use App\Domain\Auth\Enums\LoginRiskLevel;

final readonly class AuthRiskResult
{
    public function __construct(
        public int $riskScore,
        public LoginRiskLevel $riskLevel,
        public bool $requiresOtp,
        public bool $shouldBlock,
        public ?string $reason = null,
    ) {}

    public function isLowRisk(): bool
    {
        return $this->riskLevel === LoginRiskLevel::LOW;
    }

    public function isMediumRisk(): bool
    {
        return $this->riskLevel === LoginRiskLevel::MEDIUM;
    }

    public function isHighRisk(): bool
    {
        return $this->riskLevel === LoginRiskLevel::HIGH;
    }

    public function toArray(): array
    {
        return [
            'risk_score' => $this->riskScore,
            'risk_level' => $this->riskLevel->value,
            'requires_otp' => $this->requiresOtp,
            'should_block' => $this->shouldBlock,
            'reason' => $this->reason,
        ];
    }
}