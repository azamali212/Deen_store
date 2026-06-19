<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\DTO;

use App\Domain\Auth\Enums\OtpPurpose;
use Carbon\CarbonInterface;

final readonly class CreateOtpData
{
    public function __construct(
        public string $identifier,
        public string $codeHash,
        public OtpPurpose $purpose,
        public CarbonInterface $expiresAt,
        public ?string $userId = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'identifier' => $this->identifier,
            'code' => $this->codeHash,
            'purpose' => $this->purpose->value,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'expires_at' => $this->expiresAt,
        ], static fn ($value): bool => $value !== null);
    }
}