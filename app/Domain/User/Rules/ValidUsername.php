<?php

declare(strict_types=1);

namespace App\Domain\User\Rules;

use App\Domain\User\Exceptions\InvalidUsernameException;
use App\Domain\User\ValueObjects\Username;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidUsername implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            Username::from((string) $value);
        } catch (InvalidUsernameException $e) {
            $fail($e->getMessage());
        }
    }
}
