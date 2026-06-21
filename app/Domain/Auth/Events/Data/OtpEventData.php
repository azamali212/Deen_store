<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events\Data;

use App\Domain\Auth\Enums\OtpPurpose;
use Carbon\CarbonInterface;

final readonly class OtpEventData
{
    public function __construct(
        public string $userId,
        public string $identifier,
        public OtpPurpose $purpose,
        public ?string $ipAddress,
        public ?string $userAgent,
        public CarbonInterface $occurredAt,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'identifier' => $this->identifier,
            'purpose' => $this->purpose->value,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'occurred_at' => $this->occurredAt,
        ];
    }
}