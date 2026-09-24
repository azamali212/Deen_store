<?php

declare(strict_types=1);

namespace App\Domain\Seller\DTO;

use App\Support\Concerns\HasDtoHelpers;
use InvalidArgumentException;

final readonly class ReviewSellerApplicationDTO
{
    use HasDtoHelpers;

    public const APPROVE = 'approve';

    public const REJECT = 'reject';

    public function __construct(
        public string $decision,
        public ?string $reason,
        public int $reviewerId,
    ) {
        if (! in_array($decision, [self::APPROVE, self::REJECT], true)) {
            throw new InvalidArgumentException("Unknown review decision: {$decision}");
        }

        if ($decision === self::REJECT && ($reason === null || $reason === '')) {
            throw new InvalidArgumentException('A rejection reason is required.');
        }
    }

    public static function fromArray(array $data, int $reviewerId): self
    {
        return new self(
            decision: self::requiredString($data, 'decision'),
            reason: self::nullableString($data, 'reason'),
            reviewerId: $reviewerId,
        );
    }

    public function isApproval(): bool
    {
        return $this->decision === self::APPROVE;
    }
}
