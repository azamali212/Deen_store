<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 409 — P6-2 state errors on either side of the proof flow.
final class InvalidBankVerificationStateException extends DomainException
{
    public static function noBankAccount(): self
    {
        return new self('Add your payout bank account first, then upload a statement for it.');
    }

    public static function alreadyVerified(): self
    {
        return new self('Your payout bank account is already verified — no proof is needed.');
    }

    public static function awaitingReview(): self
    {
        return new self('Your bank statement is already with our team for review.');
    }

    public static function nothingToReview(int $sellerProfileId): self
    {
        return (new self('This store has no bank statement waiting for review.'))
            ->withContext(['seller_profile_id' => $sellerProfileId]);
    }
}
