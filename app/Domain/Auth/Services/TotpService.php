<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Support\TotpGenerator;

final readonly class TotpService
{
    public function __construct(
        private TotpGenerator $generator,
    ) {}

    public function generateSecret(): string
    {
        return $this->generator
            ->generateSecret();
    }

    public function verify(
        string $secret,
        string $code,
    ): bool {

        return $this->generator
            ->verify(
                secret: $secret,
                code: $code,
            );
    }

    public function generateQrCodeUri(
        string $email,
        string $secret,
    ): string {

        return $this->generator
            ->uri(
                issuer: config(
                    'app.name',
                ),
                email: $email,
                secret: $secret,
            );
    }
}