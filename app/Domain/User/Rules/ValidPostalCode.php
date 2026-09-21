<?php

declare(strict_types=1);

namespace App\Domain\User\Rules;

use App\Domain\User\ValueObjects\PostalCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

final class ValidPostalCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            PostalCode::from((string) $value);
        } catch (InvalidArgumentException) {
            $fail('The :attribute is not a valid postal code.');
        }
    }
}
