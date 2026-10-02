<?php

declare(strict_types=1);

namespace App\Domain\Seller\Data;

use App\Domain\Seller\Enums\BankAccountFormat;

/**
 * P11-1 — which shape of bank identifier each country uses, as DATA.
 * Adding a country is one entry.
 *
 * IBAN_LENGTHS is separate and larger on purpose: it is keyed by the
 * IBAN's OWN two-letter prefix, not by where the seller lives. P11-2 lets
 * anyone pay us with an IBAN, so we have to be able to check an IBAN from
 * a country that is not in the format list at all.
 */
final class BankAccountFormatMap
{
    public const FALLBACK = '__default';

    /**
     * Official IBAN lengths. An IBAN whose prefix is not listed is still
     * accepted if the checksum passes — we just cannot check its length.
     */
    private const IBAN_LENGTHS = [
        'AT' => 20, 'BE' => 16, 'BG' => 22, 'CH' => 21, 'CY' => 28,
        'CZ' => 24, 'DE' => 22, 'DK' => 18, 'EE' => 20, 'ES' => 24,
        'FI' => 18, 'FR' => 27, 'GB' => 22, 'GR' => 27, 'HR' => 21,
        'HU' => 28, 'IE' => 22, 'IS' => 26, 'IT' => 27, 'LI' => 21,
        'LT' => 20, 'LU' => 20, 'LV' => 21, 'MT' => 31, 'NL' => 18,
        'NO' => 15, 'PK' => 24, 'PL' => 28, 'PT' => 25, 'RO' => 24,
        'SE' => 24, 'SI' => 19, 'SK' => 24, 'TR' => 26, 'AE' => 23,
        'SA' => 24, 'QA' => 29, 'BH' => 22,
    ];

    /**
     * @return array{format: BankAccountFormat, branch_digits?: int, account_min?: int, account_max?: int, branch_label?: string}
     */
    public static function for(string $country): array
    {
        return self::all()[strtoupper($country)] ?? self::all()[self::FALLBACK];
    }

    public static function ibanLength(string $ibanCountryPrefix): ?int
    {
        return self::IBAN_LENGTHS[strtoupper($ibanCountryPrefix)] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function all(): array
    {
        $iban = ['format' => BankAccountFormat::IBAN];

        return [
            'PK' => $iban,
            'IE' => $iban,
            'DE' => $iban,
            'FR' => $iban,
            'ES' => $iban,
            'IT' => $iban,
            'NL' => $iban,

            // The UK has IBANs, but a UK seller knows their sort code and
            // account number — that is what their bank prints. P11-2 still
            // lets them paste an IBAN instead.
            'GB' => [
                'format' => BankAccountFormat::BRANCH_AND_ACCOUNT,
                'branch_digits' => 6,
                'account_min' => 8,
                'account_max' => 8,
                'branch_label' => 'sort code',
            ],

            'US' => [
                'format' => BankAccountFormat::BRANCH_AND_ACCOUNT,
                'branch_digits' => 9,
                'account_min' => 4,
                'account_max' => 17,
                'branch_label' => 'routing number',
            ],

            self::FALLBACK => [
                'format' => BankAccountFormat::BASIC,
                'account_min' => 8,
                'account_max' => 24,
            ],
        ];
    }
}
