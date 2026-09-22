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

    // userId is a required, explicit argument — never read from $data.
    // This is a self-service endpoint; the caller must pass
    // $request->user()->id, not trust a client-supplied user_id (the same
    // rule that keeps self-registration from letting a caller pick its
    // own role — see CreateUserDTO::forSelfRegistration()).
    public static function fromArray(
        array $data,
        int $userId,
    ): self {
        return new self(
            userId: $userId,
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
