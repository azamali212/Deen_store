<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class ChangePasswordDTO
{
    public function __construct(
        public string $userId,
        public string $currentPassword,
        public string $newPassword,
    ) {}

    public static function fromArray(
        array $data,
        string $userId,
    ): self {

        return new self(

            userId: $userId,

            currentPassword: (string) $data['current_password'],

            newPassword: (string) $data['new_password'],

        );
    }
}