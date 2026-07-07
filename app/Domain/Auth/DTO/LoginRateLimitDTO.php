<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\AuthPanel;

final readonly class LoginRateLimitDTO
{
    public function __construct(
        public string $email,
        public AuthPanel $panel,
        public ?string $ipAddress = null,
    ) {}

    public static function make(
        string $email,
        AuthPanel $panel,
        ?string $ipAddress = null,
    ): self {

        return new self(
            email: strtolower(
                trim(
                    $email,
                ),
            ),
            panel: $panel,
            ipAddress: $ipAddress,
        );
    }
}
