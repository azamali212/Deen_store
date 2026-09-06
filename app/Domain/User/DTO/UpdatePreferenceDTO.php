<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

use App\Support\Concerns\HasDtoHelpers;

final readonly class UpdatePreferenceDTO
{
    use HasDtoHelpers;

    public function __construct(
        public int $userId,
        public string $language,
        public string $currency,
        public string $timezone,
        public bool $emailNotifications,
        public bool $smsNotifications,
        public bool $pushNotifications,
        public bool $marketingNotifications,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: self::requiredInt($data, 'user_id'),
            language: self::requiredString($data, 'language'),
            currency: self::requiredString($data, 'currency'),
            timezone: self::requiredString($data, 'timezone'),
            emailNotifications: self::boolean($data, 'email_notifications', true),
            smsNotifications: self::boolean($data, 'sms_notifications', false),
            pushNotifications: self::boolean($data, 'push_notifications', true),
            marketingNotifications: self::boolean($data, 'marketing_notifications', true),
        );
    }
}
