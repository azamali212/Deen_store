<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class ResendVerificationDTO
{
    public function __construct(
        public string $email,
    ) {}

    public static function fromArray(
        array $data
    ): self {

        return new self(

            email: strtolower(
                trim(
                    (string) $data['email']
                )
            ),

        );
    }
}