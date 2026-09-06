<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObjects;

use InvalidArgumentException;

final readonly class AvatarPath
{
    public function __construct(
        private string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException(
                'Avatar path cannot be empty.',
            );
        }
    }

    public static function from(
        string $path,
    ): self {
        return new self(
            trim($path),
        );
    }

    public function value(): string
    {
        return $this->value;
    }

    public function filename(): string
    {
        return basename(
            $this->value,
        );
    }

    public function extension(): string
    {
        return strtolower(
            pathinfo(
                $this->value,
                PATHINFO_EXTENSION,
            ),
        );
    }

    public function equals(
        self $avatarPath,
    ): bool {
        return $this->value === $avatarPath->value();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
