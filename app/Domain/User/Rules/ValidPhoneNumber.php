<?php

declare(strict_types=1);

namespace App\Domain\User\Rules;

use App\Domain\User\ValueObjects\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

/**
 * Thin adapter around the PhoneNumber Value Object — reuses its exact
 * format rule instead of re-typing the regex here, so the two can never
 * drift apart. Catching a bad value here (a clean 422) is strictly better
 * than letting it reach the DTO and throw InvalidArgumentException there.
 */
final class ValidPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            PhoneNumber::from((string) $value);
        } catch (InvalidArgumentException) {
            $fail('The :attribute must be a valid phone number, e.g. +923001234567.');
        }
    }
}
