<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

use App\Support\Concerns\HasDtoHelpers;

final readonly class UpdatePreferenceDTO
{
    use HasDtoHelpers;

    public function __construct(
        public string $language,
        public string $currency,
        public string $timezone,
        public string $theme,
        public bool $emailNotifications,
        public bool $smsNotifications,
        public bool $pushNotifications,
        public bool $marketingNotifications,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            language: self::nullableString($data, 'language') ?? 'en',
            currency: self::nullableString($data, 'currency') ?? 'USD',
            timezone: self::nullableString($data, 'timezone') ?? 'UTC',
            theme: self::nullableString($data, 'theme') ?? 'system',
            emailNotifications: self::boolean($data, 'email_notifications', true),
            smsNotifications: self::boolean($data, 'sms_notifications', false),
            pushNotifications: self::boolean($data, 'push_notifications', true),
            marketingNotifications: self::boolean($data, 'marketing_notifications', true),
        );
    }
}
