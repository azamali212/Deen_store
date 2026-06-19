<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\DTO;

use App\Domain\Auth\Enums\UserAccountStatus;

final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $passwordHash,
        public UserAccountStatus $status = UserAccountStatus::ACTIVE,
        public ?string $phone = null,
        public ?string $createdByUserId = null,
        public bool $emailVerified = false,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->passwordHash,
            'status' => $this->status->value,
            'phone' => $this->phone,
            'created_by' => $this->createdByUserId,
            'email_verified_at' => $this->emailVerified ? now() : null,
        ], static fn ($value): bool => $value !== null);
    }
}