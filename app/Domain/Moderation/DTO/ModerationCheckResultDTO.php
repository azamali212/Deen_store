<?php

declare(strict_types=1);

namespace App\Domain\Moderation\DTO;

use App\Domain\Moderation\Enums\ModerationSeverity;

final readonly class ModerationCheckResultDTO
{
    /**
     * @param  array<string, array{category: string, reason: string}>  $flaggedFields
     */
    public function __construct(
        public bool $isClean,
        public ?ModerationSeverity $severity,
        public array $flaggedFields,
        public string $summary,
    ) {}

    public static function clean(string $summary): self
    {
        return new self(
            isClean: true,
            severity: null,
            flaggedFields: [],
            summary: $summary,
        );
    }

    /**
     * @param  array<string, array{category: string, reason: string}>  $flaggedFields
     */
    public static function flagged(
        ModerationSeverity $severity,
        array $flaggedFields,
        string $summary,
    ): self {
        return new self(
            isClean: false,
            severity: $severity,
            flaggedFields: $flaggedFields,
            summary: $summary,
        );
    }
}
