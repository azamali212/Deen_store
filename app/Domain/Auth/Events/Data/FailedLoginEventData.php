<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events\Data;

use App\Domain\Auth\Enums\AuthPanel;
use Carbon\CarbonInterface;

final readonly class FailedLoginEventData
{
    public function __construct(
        public string $identifier,
        public ?AuthPanel $panel,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $deviceName,
        public string $reason,
        public CarbonInterface $occurredAt,
    ) {}

    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'panel' => $this->panel?->value,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'device_name' => $this->deviceName,
            'reason' => $this->reason,
            'occurred_at' => $this->occurredAt,
        ];
    }
}