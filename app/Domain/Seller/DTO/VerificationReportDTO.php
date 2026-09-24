<?php

declare(strict_types=1);

namespace App\Domain\Seller\DTO;

use App\Domain\Seller\Enums\AiRiskLevel;
use App\Domain\Seller\Enums\VerificationCheckResult;

/**
 * Layer 2 result — the report the admin sees (Layer 3).
 *
 * Risk rule (kept simple and explainable on purpose):
 *   skipped                          -> unknown
 *   any FAIL                         -> high   (submit is blocked anyway)
 *   any tamper warning, or 3+ warns  -> high
 *   1–2 warnings                     -> medium
 *   no warnings                      -> low
 */
final readonly class VerificationReportDTO
{
    /**
     * @param  array<int, array{key: string, result: string, message: string}>  $checks
     */
    private function __construct(
        public bool $skipped,
        public array $checks,
    ) {}

    public static function skipped(): self
    {
        return new self(true, [[
            'key' => 'ai_verification',
            'result' => VerificationCheckResult::SKIPPED->value,
            'message' => 'Automated document verification was not run — review every document manually.',
        ]]);
    }

    /**
     * @param  array<int, array{key: string, result: string, message: string}>  $checks
     */
    public static function fromChecks(array $checks): self
    {
        return new self(false, array_values($checks));
    }

    /**
     * Customer-facing messages of the hard failures only.
     *
     * @return array<int, string>
     */
    public function failures(): array
    {
        return array_values(array_map(
            static fn (array $check): string => $check['message'],
            array_filter($this->checks, static fn (array $check): bool => $check['result'] === VerificationCheckResult::FAIL->value),
        ));
    }

    public function riskLevel(): AiRiskLevel
    {
        if ($this->skipped) {
            return AiRiskLevel::UNKNOWN;
        }

        if ($this->count(VerificationCheckResult::FAIL) > 0) {
            return AiRiskLevel::HIGH;
        }

        $warnings = $this->count(VerificationCheckResult::WARN);

        $hasTamperWarning = array_filter(
            $this->checks,
            static fn (array $check): bool => str_starts_with($check['key'], 'tamper_') && $check['result'] === VerificationCheckResult::WARN->value,
        ) !== [];

        if ($hasTamperWarning || $warnings >= 3) {
            return AiRiskLevel::HIGH;
        }

        return $warnings > 0 ? AiRiskLevel::MEDIUM : AiRiskLevel::LOW;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->skipped ? 'skipped' : 'completed',
            'risk_level' => $this->riskLevel()->value,
            'summary' => [
                'passed' => $this->count(VerificationCheckResult::PASS),
                'warnings' => $this->count(VerificationCheckResult::WARN),
                'failed' => $this->count(VerificationCheckResult::FAIL),
            ],
            'checks' => $this->checks,
        ];
    }

    private function count(VerificationCheckResult $result): int
    {
        return count(array_filter(
            $this->checks,
            static fn (array $check): bool => $check['result'] === $result->value,
        ));
    }
}
