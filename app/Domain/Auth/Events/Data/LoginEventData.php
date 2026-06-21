<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events\Data;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\LoginProvider;
use Carbon\CarbonInterface;

final readonly class LoginEventData
{
    public function __construct(
        public string $userId,
        public string $email,
        public AuthPanel $panel,
        public LoginProvider $provider,
        public string $ipAddress,
        public ?string $userAgent,
        public ?string $deviceName,
        public ?string $browser,
        public ?string $operatingSystem,
        public int $riskScore,
        public CarbonInterface $occurredAt,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'panel' => $this->panel->value,
            'provider' => $this->provider->value,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'device_name' => $this->deviceName,
            'browser' => $this->browser,
            'operating_system' => $this->operatingSystem,
            'risk_score' => $this->riskScore,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
