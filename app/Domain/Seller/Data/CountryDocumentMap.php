<?php

declare(strict_types=1);

namespace App\Domain\Seller\Data;

use App\Domain\Seller\Enums\SellerDocumentType;

/**
 * P10-2 — which documents each country needs, as DATA. Adding a country is
 * one entry here and nothing else: no new enum case, no new code path.
 *
 * P10-3 — a requirement is a GROUP with OPTIONS, and each option is a set
 * of types. Pakistan's identity group has one option needing two documents
 * (CNIC front AND back); the UK's has two options (a passport, OR a
 * driving licence front and back).
 *
 * THESE RULES HAVE NOT BEEN CHECKED WITH A COMPLIANCE ADVISER. They are the
 * ordinary KYC patterns for each country, good enough to build and test
 * against, and they must be reviewed before real sellers are onboarded in
 * any of these places. Reviewing them is reading this one file.
 */
final class CountryDocumentMap
{
    /** Used for every country not named below (P10-4). */
    public const FALLBACK = '__default';

    /**
     * Pakistan's identity number is exactly 13 digits. That rule lives HERE
     * with the country it belongs to, not inside the AI verifier where it
     * used to quietly discard every passport number (C52).
     */
    private const IDENTITY_NUMBER_DIGITS = [
        'PK' => 13,
    ];

    /**
     * @return array<string, array<int, array<int, SellerDocumentType>>>
     */
    public static function for(string $country): array
    {
        return self::all()[strtoupper($country)] ?? self::all()[self::FALLBACK];
    }

    public static function supports(string $country): bool
    {
        return isset(self::all()[strtoupper($country)]);
    }

    /**
     * Countries with rules of their own. Everywhere else still works — it
     * gets the fallback — so this is NOT a list of where we operate.
     *
     * @return array<int, string>
     */
    public static function named(): array
    {
        return array_values(array_filter(
            array_keys(self::all()),
            fn (string $code): bool => $code !== self::FALLBACK,
        ));
    }

    /** How many digits this country's identity number has, if it is fixed. */
    public static function identityNumberDigits(string $country): ?int
    {
        return self::IDENTITY_NUMBER_DIGITS[strtoupper($country)] ?? null;
    }

    /**
     * @return array<string, array<string, array<int, array<int, SellerDocumentType>>>>
     */
    private static function all(): array
    {
        $business = [
            'business' => [[SellerDocumentType::BUSINESS_LICENSE]],
            'tax' => [[SellerDocumentType::TAX_CERTIFICATE]],
            'bank' => [[SellerDocumentType::BANK_STATEMENT]],
        ];

        // A passport, or a national ID card (both sides) — the ordinary
        // pattern across the EU, where ID cards are standard.
        $passportOrNationalId = [[SellerDocumentType::PASSPORT], [
            SellerDocumentType::NATIONAL_ID_FRONT,
            SellerDocumentType::NATIONAL_ID_BACK,
        ]];

        return [
            'PK' => ['identity' => [[
                SellerDocumentType::CNIC_FRONT,
                SellerDocumentType::CNIC_BACK,
            ]]] + $business,

            // The UK has no national ID card, so the second option is a
            // driving licence.
            'GB' => ['identity' => [[SellerDocumentType::PASSPORT], [
                SellerDocumentType::DRIVING_LICENCE_FRONT,
                SellerDocumentType::DRIVING_LICENCE_BACK,
            ]]] + $business,

            'IE' => ['identity' => [[SellerDocumentType::PASSPORT], [
                SellerDocumentType::DRIVING_LICENCE_FRONT,
                SellerDocumentType::DRIVING_LICENCE_BACK,
            ]]] + $business,

            'DE' => ['identity' => $passportOrNationalId] + $business,
            'FR' => ['identity' => $passportOrNationalId] + $business,
            'ES' => ['identity' => $passportOrNationalId] + $business,
            'IT' => ['identity' => $passportOrNationalId] + $business,
            'NL' => ['identity' => $passportOrNationalId] + $business,

            // P10-4 — everywhere else. A passport is the one identity
            // document that exists in every country, so this is honest
            // rather than a guess at rules we have not checked.
            self::FALLBACK => ['identity' => [[SellerDocumentType::PASSPORT]]] + $business,
        ];
    }
}
