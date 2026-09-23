<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\Http;

/**
 * Fakes the Gemini HTTP call that ProfileModerationAiService makes, so tests
 * never hit the real network — fast, free, and deterministic. We fake at the
 * HTTP layer (not by mocking the service) so the real prompt-building and
 * JSON-parsing code still runs and gets exercised by the test.
 */
trait FakesGeminiModeration
{
    protected function fakeGeminiClean(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->geminiEnvelope([
                    'clean' => true,
                    'severity' => null,
                    'summary' => 'No issues found.',
                    'flagged_fields' => [],
                ]),
                200,
            ),
        ]);
    }

    /**
     * @param  array<string, array{category: string, reason: string}>  $flaggedFields
     */
    protected function fakeGeminiFlagged(array $flaggedFields, string $severity = 'medium'): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->geminiEnvelope([
                    'clean' => false,
                    'severity' => $severity,
                    'summary' => 'Content was flagged for review.',
                    'flagged_fields' => $flaggedFields,
                ]),
                200,
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $decodedJson
     * @return array<string, mixed>
     */
    private function geminiEnvelope(array $decodedJson): array
    {
        return [
            'candidates' => [
                [
                    'finishReason' => 'STOP',
                    'content' => [
                        'parts' => [
                            ['text' => json_encode($decodedJson)],
                        ],
                    ],
                ],
            ],
        ];
    }
}
