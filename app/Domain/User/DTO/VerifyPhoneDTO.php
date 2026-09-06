<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

use App\Domain\User\ValueObjects\PhoneNumber;
use App\Support\Concerns\HasDtoHelpers;

final readonly class VerifyPhoneDTO
{
    use HasDtoHelpers;

    public function __construct(
        public int $userId,
        public PhoneNumber $phone,
        public string $code,
    ) {}

    public static function fromArray(
        array $data,
    ): self {
        return new self(
            userId: self::requiredInt(
                $data,
                'user_id',
            ),
            phone: PhoneNumber::from(
                self::requiredString(
                    $data,
                    'phone',
                ),
            ),
            code: self::requiredString(
                $data,
                'code',
            ),
        );
    }
}
