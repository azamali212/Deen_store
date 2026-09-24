<?php

declare(strict_types=1);

namespace App\Domain\Seller\DTO;

use App\Support\Concerns\HasDtoHelpers;

/**
 * Only the EDITABLE fields (C6) — store_name / business_name /
 * business_type are refused by the request before they get this far.
 */
final readonly class UpdateSellerProfileDTO
{
    use HasDtoHelpers;

    /**
     * @param  array<string, string|null>  $profileFields  only the keys that were sent
     *                                                     (description, business_address)
     */
    public function __construct(
        public array $profileFields,
        public ?string $bankAccountTitle,
        public ?string $bankName,
        public ?string $bankAccountNumber,
    ) {}

    public static function fromArray(array $data): self
    {
        $profileFields = [];

        // array_key_exists, not isset: sending "description": null is a
        // deliberate "clear it", not "leave unchanged".
        foreach (['description', 'business_address'] as $key) {
            if (array_key_exists($key, $data)) {
                $profileFields[$key] = self::nullableString($data, $key);
            }
        }

        return new self(
            profileFields: $profileFields,
            bankAccountTitle: self::nullableString($data, 'bank_account_title'),
            bankName: self::nullableString($data, 'bank_name'),
            // Already normalised by the request (spaces/dashes removed,
            // upper-cased), so "PK36 SCBL ..." and "PK36SCBL..." are equal.
            bankAccountNumber: self::nullableString($data, 'bank_account_number'),
        );
    }

    // The request makes the three bank fields all-or-nothing.
    public function hasBankDetails(): bool
    {
        return $this->bankAccountNumber !== null;
    }

    /**
     * Free text that must pass AI moderation. Only `description`:
     * business_address is deliberately NOT sent — the moderation prompt
     * flags "exposed contact info", and a store address is supposed to be
     * public, so it would be a false positive every time.
     *
     * @return array<string, string>
     */
    public function publicText(): array
    {
        $description = $this->profileFields['description'] ?? null;

        return $description !== null && $description !== ''
            ? ['description' => $description]
            : [];
    }
}
