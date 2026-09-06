<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

use App\Domain\Auth\Enums\UserAccountStatus;
use App\Support\Concerns\HasDtoHelpers;

final readonly class UserStatusDTO
{
    use HasDtoHelpers;

    public function __construct(
        public int $userId,
        public UserAccountStatus $status,
        public ?string $reason,
    ) {}

    public static function fromArray(
        array $data,
    ): self {
        return new self(
            userId: self::requiredInt(
                $data,
                'user_id',
            ),
            status: UserAccountStatus::from(
                self::requiredString(
                    $data,
                    'status',
                ),
            ),
            reason: self::nullableString(
                $data,
                'reason',
            ),
        );
    }
}
