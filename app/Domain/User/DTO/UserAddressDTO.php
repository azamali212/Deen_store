<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

use App\Domain\User\Enums\AddressType;
use App\Domain\User\ValueObjects\PhoneNumber;
use App\Domain\User\ValueObjects\PostalCode;
use App\Support\Concerns\HasDtoHelpers;

final readonly class UserAddressDTO
{
    use HasDtoHelpers;

    public function __construct(
        public AddressType $type,
        public bool $isDefault,
        public ?string $label,
        public string $recipientName,
        public PhoneNumber $phone,
        public string $addressLine1,
        public ?string $addressLine2,
        public string $city,
        public ?string $state,
        public ?PostalCode $postalCode,
        public string $countryCode,
        public ?float $latitude,
        public ?float $longitude,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: AddressType::from(self::requiredString($data, 'type')),
            isDefault: self::boolean($data, 'is_default', false),
            label: self::nullableString($data, 'label'),
            recipientName: self::requiredString($data, 'recipient_name'),
            phone: PhoneNumber::from(self::requiredString($data, 'phone')),
            addressLine1: self::requiredString($data, 'address_line_1'),
            addressLine2: self::nullableString($data, 'address_line_2'),
            city: self::requiredString($data, 'city'),
            state: self::nullableString($data, 'state'),
            postalCode: self::nullableString($data, 'postal_code') !== null
                ? PostalCode::from($data['postal_code'])
                : null,
            countryCode: self::requiredString($data, 'country_code'),
            latitude: self::nullableFloat($data, 'latitude'),
            longitude: self::nullableFloat($data, 'longitude'),
        );
    }
}
