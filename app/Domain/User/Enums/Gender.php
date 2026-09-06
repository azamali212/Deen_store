<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';
    case PREFER_NOT_TO_SAY = 'prefer_not_to_say';

    public static function values(): array
    {
        return array_column(
            self::cases(),
            'value',
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
            self::OTHER => 'Other',
            self::PREFER_NOT_TO_SAY => 'Prefer not to say',
        };
    }

    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases(),
        );
    }
}
