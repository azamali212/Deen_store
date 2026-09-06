<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

use App\Domain\Auth\Enums\UserAccountStatus;
use App\Support\Concerns\HasDtoHelpers;

final readonly class UserFilterDTO
{
    use HasDtoHelpers;

    public function __construct(
        public ?string $search,
        public ?UserAccountStatus $status,
        public ?string $role,
        public ?bool $emailVerified,
        public ?bool $phoneVerified,
        public int $perPage,
    ) {}

    public static function fromArray(
        array $data,
    ): self {
        return new self(
            search: self::nullableString(
                $data,
                'search',
            ),
            status: isset($data['status'])
                ? UserAccountStatus::from(
                    $data['status'],
                )
                : null,
            role: self::nullableString(
                $data,
                'role',
            ),
            emailVerified: isset($data['email_verified'])
                ? self::boolean(
                    $data,
                    'email_verified',
                )
                : null,
            phoneVerified: isset($data['phone_verified'])
                ? self::boolean(
                    $data,
                    'phone_verified',
                )
                : null,
            perPage: self::nullableInt(
                $data,
                'per_page',
            ) ?? 15,
        );
    }
}
