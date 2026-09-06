<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObjects;

use InvalidArgumentException;

final readonly class PostalCode
{
    public function __construct(
        private string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException(
                'Postal code cannot be empty.',
            );
        }
    }

    public static function from(
        string $postalCode,
    ): self {
        return new self(
            strtoupper(
                trim($postalCode),
            ),
        );
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(
        self $postalCode,
    ): bool {
        return $this->value === $postalCode->value();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
