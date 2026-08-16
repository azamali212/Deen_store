<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\TwoFactorProvider;
use Carbon\CarbonInterface;

final readonly class UpdateTwoFactorData
{
    public function __construct(
        public bool $enabled,
        public ?TwoFactorProvider $provider,
        public ?string $secret,
        public ?CarbonInterface $confirmedAt = null,
        public ?CarbonInterface $lastVerifiedAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'two_factor_enabled' => $this->enabled,
            'two_factor_provider' => $this->provider?->value,
            'two_factor_secret' => $this->secret,
            'two_factor_confirmed_at' => $this->confirmedAt,
            'two_factor_last_verified_at' => $this->lastVerifiedAt,
        ];
    }
}
