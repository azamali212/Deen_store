<?php

declare(strict_types=1);

namespace App\Domain\Seller\DTO;

use App\Domain\Seller\Enums\DocumentAiStatus;
use App\Domain\Seller\Enums\DocumentRejectionCategory;

/**
 * What a DocumentVerifier says about ONE uploaded file (Layer 1).
 */
final readonly class DocumentVerificationResultDTO
{
    /**
     * @param  array<string, string|null>  $fields    sanitised values read off the document
     * @param  array<int, string>  $concerns          e.g. possible editing — flagged, not blocking
     */
    private function __construct(
        public bool $skipped,
        public ?DocumentRejectionCategory $rejection,
        public array $fields,
        public array $concerns,
    ) {}

    // AI switched off — document accepted for manual review.
    public static function skipped(): self
    {
        return new self(true, null, [], []);
    }

    public static function accepted(array $fields, array $concerns = []): self
    {
        return new self(false, null, $fields, array_values($concerns));
    }

    public static function rejected(DocumentRejectionCategory $category): self
    {
        return new self(false, $category, [], []);
    }

    public function isAcceptable(): bool
    {
        return $this->rejection === null;
    }

    public function aiStatus(): DocumentAiStatus
    {
        if ($this->skipped) {
            return DocumentAiStatus::SKIPPED;
        }

        return $this->concerns === [] ? DocumentAiStatus::PASSED : DocumentAiStatus::FLAGGED;
    }
}
