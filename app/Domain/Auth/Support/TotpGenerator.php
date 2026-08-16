<?php

declare(strict_types=1);

namespace App\Domain\Auth\Support;

use PragmaRX\Google2FA\Google2FA;

final readonly class TotpGenerator
{
    public function __construct(
        private Google2FA $google2FA,
    ) {}

    public function generateSecret(): string
    {
        return $this->google2FA
            ->generateSecretKey();
    }

    public function verify(
        string $secret,
        string $code,
    ): bool {

        return $this->google2FA
            ->verifyKey(
                $secret,
                $code,
            );
    }

    public function uri(
        string $issuer,
        string $email,
        string $secret,
    ): string {

        return $this->google2FA
            ->getQRCodeUrl(
                $issuer,
                $email,
                $secret,
            );
    }
}