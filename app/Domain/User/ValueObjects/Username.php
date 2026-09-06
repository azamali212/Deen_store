<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObjects;

use App\Domain\User\Exceptions\InvalidUsernameException;

final readonly class Username
{
    public function __construct(
        private string $value,
    ) {
        $trimmed = trim($this->value);

        if (strlen($trimmed) < 3 || strlen($trimmed) > 20) {
            throw new InvalidUsernameException(
                'Username must be between 3 and 20 characters.',
            );
        }

        if (! preg_match('/^[a-zA-Z0-9_.]+$/', $trimmed)) {
            throw new InvalidUsernameException(
                'Username can only contain letters, numbers, underscores and dots.',
            );
        }
    }

    public static function from(string $value): self
    {
        return new self(strtolower(trim($value)));
    }

    public function value(): string
    {
        return strtolower(trim($this->value));
    }

    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }

    public function __toString(): string
    {
        return $this->value();
    }
}
