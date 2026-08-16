<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use Illuminate\Contracts\Encryption\Encrypter;

final readonly class TwoFactorSecretService
{
    public function __construct(
        private Encrypter $encrypter,
    ) {}

    public function encrypt(
        string $secret,
    ): string {

        return $this->encrypter
            ->encrypt(
                $secret,
            );
    }

    public function decrypt(
        string $encryptedSecret,
    ): string {

        return $this->encrypter
            ->decrypt(
                $encryptedSecret,
            );
    }
}