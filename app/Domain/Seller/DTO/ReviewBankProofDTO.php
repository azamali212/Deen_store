<?php

declare(strict_types=1);

namespace App\Domain\Seller\DTO;

use App\Support\Concerns\HasDtoHelpers;
use InvalidArgumentException;

final readonly class ReviewBankProofDTO
{
    use HasDtoHelpers;

    public const VERIFY = 'verify';

    public const REJECT = 'reject';

    public function __construct(
        public string $decision,
        public ?string $reason,
        public int $adminId,
    ) {
        if (! in_array($decision, [self::VERIFY, self::REJECT], true)) {
            throw new InvalidArgumentException("Unknown bank review decision: {$decision}");
        }

        if ($decision === self::REJECT && ($reason === null || $reason === '')) {
            throw new InvalidArgumentException('A rejection reason is required.');
        }
    }

    public static function fromArray(array $data, int $adminId): self
    {
        return new self(
            decision: self::requiredString($data, 'decision'),
            reason: self::nullableString($data, 'reason'),
            adminId: $adminId,
        );
    }

    public function isVerification(): bool
    {
        return $this->decision === self::VERIFY;
    }
}
