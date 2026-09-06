<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObjects;

use InvalidArgumentException;

final readonly class PhoneNumber
{
    public function __construct(
        private string $value,
    ) {
        if (! preg_match('/^\+?[1-9]\d{7,14}$/', $this->value)) {
            throw new InvalidArgumentException(
                'Invalid phone number.',
            );
        }
    }

    public static function from(
        string $phone,
    ): self {
        return new self(
            trim($phone),
        );
    }

    public function value(): string
    {
        return $this->value;
    }

    public function formatted(): string
    {
        return $this->value;
    }

    public function equals(
        self $phone,
    ): bool {
        return $this->value === $phone->value();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
