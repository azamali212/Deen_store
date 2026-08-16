<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Enums\TwoFactorProvider;
use App\Domain\Auth\Support\TotpGenerator;

final readonly class TwoFactorQrCodeService
{
    public function __construct(
        private TotpGenerator $generator,
    ) {}

    public function generate(
        string $email,
        string $secret,
        TwoFactorProvider $provider,
    ): string {

        return $this->generator->uri(
            issuer: config('app.name'),
            email: $email,
            secret: $secret,
            // provider: $provider,
        );
    }
}