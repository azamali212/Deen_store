<?php

declare(strict_types=1);

namespace App\Domain\Seller\Support;

use App\Domain\Seller\Contracts\DocumentVerifierInterface;
use App\Domain\Seller\DTO\DocumentVerificationResultDTO;
use App\Domain\Seller\Enums\SellerDocumentType;

/**
 * Used while SELLER_AI_VERIFICATION_ENABLED=false. Sends NOTHING anywhere:
 * every document is accepted as "skipped" and the admin reviews it by eye,
 * exactly as before Phase 5.
 */
final class NullDocumentVerifier implements DocumentVerifierInterface
{
    public function verify(SellerDocumentType $type, string $bytes, string $mimeType): DocumentVerificationResultDTO
    {
        return DocumentVerificationResultDTO::skipped();
    }
}
