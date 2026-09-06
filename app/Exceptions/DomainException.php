<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

abstract class DomainException extends Exception
{
    private array $context = [];

    public function withContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function context(): array
    {
        return $this->context;
    }
}
