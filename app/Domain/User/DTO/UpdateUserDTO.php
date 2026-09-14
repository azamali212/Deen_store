<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

use App\Domain\User\ValueObjects\PhoneNumber;
use App\Support\Concerns\HasDtoHelpers;

final readonly class UpdateUserDTO
{
    use HasDtoHelpers;

    public function __construct(
        public string $name,
        public string $email,
        public ?PhoneNumber $phone,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: self::requiredString($data, 'name'),
            email: self::cleanEmail($data, 'email'),
            phone: self::nullableString($data, 'phone') !== null
                ? PhoneNumber::from($data['phone'])
                : null,
        );
    }
}
