<?php

declare(strict_types=1);

namespace App\Domain\Seller\Support;

use App\Domain\Seller\Data\BankAccountFormatMap;
use App\Domain\Seller\Exceptions\InvalidBankAccountFormatException;

/**
 * A4 — is this a bank account this seller's country could actually have?
 *
 * P11-3 is the reason this class exists at all. A regex proves the value
 * has the right SHAPE. The mod-97 checksum proves the CHARACTERS are
 * right, which is the failure that matters: a single mistyped digit in an
 * IBAN still looks perfectly well-formed, and payout money leaves for a
 * stranger's account.
 */
final class BankAccountValidator
{
    /** An IBAN is two letters, two check digits, then the rest. */
    private const IBAN_SHAPE = '/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/';

    /**
     * @throws InvalidBankAccountFormatException
     */
    public function validate(string $country, string $accountNumber, ?string $branchCode): void
    {
        $account = $this->normalise($accountNumber);

        // P11-2 — an IBAN is welcome from anybody, whatever their country's
        // domestic format is. Somebody who knows their IBAN should never be
        // told it is wrong because we wanted a sort code.
        if ($this->looksLikeIban($account)) {
            $this->assertValidIban($account);

            return;
        }

        $rules = BankAccountFormatMap::for($country);
        $format = $rules['format'];

        if ($format->needsBranchCode()) {
            $this->assertBranchCode($rules, $branchCode);
        }

        // A plain account number is digits. Letters only ever appear in
        // an IBAN, and an IBAN took the branch above — so letters here
        // mean a typo or a half-remembered IBAN, not a real account.
        if (! ctype_digit($account)) {
            throw InvalidBankAccountFormatException::notDigits();
        }

        $min = (int) ($rules['account_min'] ?? 8);
        $max = (int) ($rules['account_max'] ?? 24);
        $length = strlen($account);

        if ($length < $min || $length > $max) {
            throw InvalidBankAccountFormatException::accountLength($min, $max);
        }
    }

    /** Whether this country asks for a branch code at all. */
    public function needsBranchCode(string $country): bool
    {
        return BankAccountFormatMap::for($country)['format']->needsBranchCode();
    }

    public function normalise(string $value): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $value));
    }

    private function looksLikeIban(string $value): bool
    {
        return preg_match(self::IBAN_SHAPE, $value) === 1;
    }

    private function assertValidIban(string $iban): void
    {
        $prefix = substr($iban, 0, 2);
        $expected = BankAccountFormatMap::ibanLength($prefix);

        // An unknown prefix still gets its checksum checked — we just
        // cannot check a length we do not know.
        if ($expected !== null && strlen($iban) !== $expected) {
            throw InvalidBankAccountFormatException::ibanWrongLength($prefix, $expected, strlen($iban));
        }

        if (! $this->mod97($iban)) {
            throw InvalidBankAccountFormatException::checksumFailed();
        }
    }

    /**
     * The ISO 13616 check: move the first four characters to the end,
     * replace every letter with its position in the alphabet plus 9
     * (A=10 ... Z=35), and the whole number must leave a remainder of 1
     * when divided by 97.
     *
     * The division is done piece by piece because the number is far too
     * large for an integer — a 34-character IBAN becomes a ~40-digit
     * number, and PHP would silently turn that into a float and give the
     * wrong answer.
     */
    private function mod97(string $iban): bool
    {
        $rearranged = substr($iban, 4).substr($iban, 0, 4);

        $digits = '';

        foreach (str_split($rearranged) as $char) {
            $digits .= ctype_alpha($char)
                ? (string) (ord($char) - ord('A') + 10)
                : $char;
        }

        $remainder = 0;

        foreach (str_split($digits) as $digit) {
            if (! ctype_digit($digit)) {
                return false;
            }

            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder === 1;
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function assertBranchCode(array $rules, ?string $branchCode): void
    {
        $label = (string) ($rules['branch_label'] ?? 'branch code');
        $digits = (int) ($rules['branch_digits'] ?? 6);

        $code = $branchCode !== null ? $this->normalise($branchCode) : '';

        if ($code === '') {
            throw InvalidBankAccountFormatException::branchCodeRequired($label, $digits);
        }

        if (strlen($code) !== $digits || ! ctype_digit($code)) {
            throw InvalidBankAccountFormatException::branchCodeLength($label, $digits);
        }
    }
}
