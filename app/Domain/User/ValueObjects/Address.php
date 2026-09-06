<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObjects;

final readonly class Address
{
    public function __construct(
        private string $addressLineOne,
        private ?string $addressLineTwo,
        private string $city,
        private ?string $state,
        private string $country,
        private PostalCode $postalCode,
    ) {}

    public static function from(
        string $addressLineOne,
        ?string $addressLineTwo,
        string $city,
        ?string $state,
        string $country,
        PostalCode $postalCode,
    ): self {
        return new self(
            addressLineOne: trim($addressLineOne),
            addressLineTwo: $addressLineTwo !== null ? trim($addressLineTwo) : null,
            city: trim($city),
            state: $state !== null ? trim($state) : null,
            country: trim($country),
            postalCode: $postalCode,
        );
    }

    public function addressLineOne(): string
    {
        return $this->addressLineOne;
    }

    public function addressLineTwo(): ?string
    {
        return $this->addressLineTwo;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function state(): ?string
    {
        return $this->state;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function postalCode(): PostalCode
    {
        return $this->postalCode;
    }

    public function fullAddress(): string
    {
        return implode(
            ', ',
            array_filter([
                $this->addressLineOne,
                $this->addressLineTwo,
                $this->city,
                $this->state,
                $this->country,
                $this->postalCode->value(),
            ]),
        );
    }

    public function equals(
        self $address,
    ): bool {
        return $this->fullAddress() === $address->fullAddress();
    }
}
