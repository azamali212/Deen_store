<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

use App\Domain\User\Enums\Gender;
use App\Domain\User\Enums\ProfileVisibility;
use App\Domain\User\ValueObjects\Username;
use App\Support\Concerns\HasDtoHelpers;

final readonly class UpdateProfileDTO
{
    use HasDtoHelpers;

    public function __construct(
        public Username $username,
        public ?string $dateOfBirth,
        public ?Gender $gender,
        public ?string $bio,
        public ?string $websiteUrl,
        public ?string $occupation,

        public ?string $companyName,
        public ?string $countryCode,
        public string $timezone,
        public string $locale,
        public ProfileVisibility $profileVisibility,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            username: Username::from(self::requiredString($data, 'username')),
            dateOfBirth: self::nullableDate($data, 'date_of_birth'),
            gender: self::nullableString($data, 'gender') !== null
                ? Gender::from($data['gender'])
                : null,
            bio: self::nullableString($data, 'bio'),
            websiteUrl: self::nullableString($data, 'website_url'),
            occupation: self::nullableString($data, 'occupation'),
            companyName: self::nullableString($data, 'company_name'),
            countryCode: self::nullableString($data, 'country_code'),
            timezone: self::nullableString($data, 'timezone') ?? 'UTC',
            locale: self::nullableString($data, 'locale') ?? 'en',
            profileVisibility: self::nullableString($data, 'profile_visibility') !== null
                ? ProfileVisibility::from($data['profile_visibility'])
                : ProfileVisibility::PRIVATE,
        );
    }
}
