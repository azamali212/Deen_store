<?php

declare(strict_types=1);

namespace App\Domain\Moderation\DTO;

use App\Domain\Moderation\Enums\ModerationStatus;
use App\Support\Concerns\HasDtoHelpers;
use InvalidArgumentException;

final readonly class ResolveModerationFlagDTO
{
    use HasDtoHelpers;

    public function __construct(
        public ModerationStatus $status,
        public ?string $notes,
    ) {}

    public static function fromArray(array $data): self
    {
        $status = ModerationStatus::tryFrom(
            self::requiredString($data, 'status'),
        );

        if ($status === null || $status === ModerationStatus::PENDING) {
            throw new InvalidArgumentException(
                'status must be either "approved" or "rejected".',
            );
        }

        return new self(
            status: $status,
            notes: self::nullableString($data, 'notes'),
        );
    }
}
