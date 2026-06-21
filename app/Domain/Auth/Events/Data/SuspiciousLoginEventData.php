<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events\Data;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\LoginRiskLevel;
use Carbon\CarbonInterface;

final readonly class SuspiciousLoginEventData
{
    public function __construct(
        public string $userId,
        public string $email,
        public AuthPanel $panel,
        public LoginRiskLevel $riskLevel,
        public int $riskScore,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $deviceName,
        public ?string $reason,
        public CarbonInterface $occurredAt,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'panel' => $this->panel->value,
            'risk_level' => $this->riskLevel->value,
            'risk_score' => $this->riskScore,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'device_name' => $this->deviceName,
            'reason' => $this->reason,
            'occurred_at' => $this->occurredAt,
        ];
    }
}