<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use InvalidArgumentException;

final class PasswordPolicyService
{
    private const MIN_LENGTH = 12;

    public function ensureValid(
        string $password,
        ?string $email = null,
        ?string $name = null
    ): void {
        if (! $this->isValid($password, $email, $name)) {
            throw new InvalidArgumentException(
                'Password does not meet security requirements.'
            );
        }
    }

    public function isValid(
        string $password,
        ?string $email = null,
        ?string $name = null
    ): bool {
        if (! $this->hasMinimumLength($password)) {
            return false;
        }

        if (! $this->hasUppercase($password)) {
            return false;
        }

        if (! $this->hasLowercase($password)) {
            return false;
        }

        if (! $this->hasNumber($password)) {
            return false;
        }

        if (! $this->hasSpecialCharacter($password)) {
            return false;
        }

        if (! $this->isNotCommonPassword($password)) {
            return false;
        }

        if (! $this->doesNotContainPersonalData(
            $password,
            $email,
            $name
        )) {
            return false;
        }

        return true;
    }

    public function hasMinimumLength(string $password): bool
    {
        return mb_strlen($password) >= self::MIN_LENGTH;
    }

    public function hasUppercase(string $password): bool
    {
        return preg_match('/[A-Z]/', $password) === 1;
    }

    public function hasLowercase(string $password): bool
    {
        return preg_match('/[a-z]/', $password) === 1;
    }

    public function hasNumber(string $password): bool
    {
        return preg_match('/[0-9]/', $password) === 1;
    }

    public function hasSpecialCharacter(string $password): bool
    {
        return preg_match('/[\W_]/', $password) === 1;
    }

    public function isNotCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password',
            'password123',
            '12345678',
            '123456789',
            'qwerty',
            'admin123',
        ];

        return ! in_array(
            strtolower($password),
            $commonPasswords,
            true
        );
    }

    public function doesNotContainPersonalData(
        string $password,
        ?string $email,
        ?string $name
    ): bool {
        $password = strtolower($password);

        if ($email !== null &&
            str_contains($password, strtolower($email))) {
            return false;
        }

        if ($name !== null &&
            str_contains($password, strtolower($name))) {
            return false;
        }

        return true;
    }
}