<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class RevokeTrustedDeviceDTO
{
    public function __construct(
        public string $userId,
        public string $fingerprint,
    ) {}

    public static function fromArray(
        array $data,
        string $userId,
    ): self {

        return new self(

            userId: $userId,

            fingerprint: trim(
                (string) $data['fingerprint']
            ),

        );
    }
}