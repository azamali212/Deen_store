<?php

declare(strict_types=1);

namespace App\Domain\Seller\Contracts;

use App\Domain\Seller\DTO\DocumentVerificationResultDTO;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Exceptions\DocumentVerificationUnavailableException;

/**
 * Layer 1 — looks at ONE uploaded KYC document. Swappable on purpose:
 *   NullDocumentVerifier   — AI off (default until Gemini billing is on)
 *   GeminiDocumentVerifier — Gemini PAID tier
 *   later: NADRA Verisys, or a self-hosted model (Python/ML track)
 *
 * The verifier only READS and REPORTS. It never approves anything — the
 * admin's decision stays final.
 */
interface DocumentVerifierInterface
{
    /**
     * @throws DocumentVerificationUnavailableException when the provider is down/misconfigured
     */
    public function verify(SellerDocumentType $type, string $bytes, string $mimeType): DocumentVerificationResultDTO;
}
