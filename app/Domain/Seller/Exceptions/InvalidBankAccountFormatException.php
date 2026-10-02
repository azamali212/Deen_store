<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

/**
 * 422 — A4. Shaped like a validation error so the frontend can put the
 * message on the field the seller actually mistyped.
 */
final class InvalidBankAccountFormatException extends DomainException
{
    public readonly string $field;

    private function __construct(string $message, string $field)
    {
        parent::__construct($message);

        $this->field = $field;
    }

    public static function checksumFailed(): self
    {
        // Deliberately specific. "Invalid account number" makes people
        // retype the same wrong thing; naming the checksum tells them a
        // character is wrong, which is what mod-97 actually proves.
        return new self(
            'That IBAN did not pass its check-digit test — one character is probably wrong. Please compare it with your bank statement.',
            'bank_account_number',
        );
    }

    public static function ibanWrongLength(string $prefix, int $expected, int $given): self
    {
        return new self(
            sprintf('An IBAN from %s is %d characters long; this one is %d.', $prefix, $expected, $given),
            'bank_account_number',
        );
    }

    public static function notDigits(): self
    {
        return new self(
            'An account number should be digits only. If you meant to enter an IBAN, it starts with two letters and two digits.',
            'bank_account_number',
        );
    }

    public static function accountLength(int $min, int $max): self
    {
        return new self(
            $min === $max
                ? sprintf('Your account number should be %d digits.', $min)
                : sprintf('Your account number should be between %d and %d characters.', $min, $max),
            'bank_account_number',
        );
    }

    public static function branchCodeRequired(string $label, int $digits): self
    {
        return new self(
            sprintf('Please enter your %s (%d digits).', $label, $digits),
            'bank_branch_code',
        );
    }

    public static function branchCodeLength(string $label, int $digits): self
    {
        return new self(
            sprintf('Your %s should be %d digits.', $label, $digits),
            'bank_branch_code',
        );
    }
}
