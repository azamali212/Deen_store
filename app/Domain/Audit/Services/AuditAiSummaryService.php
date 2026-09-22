<?php

declare(strict_types=1);

namespace App\Domain\Audit\Services;

use App\Domain\Audit\DTO\AuditLogFilterDTO;
use App\Domain\Audit\Exceptions\AiSummaryUnavailableException;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Turns a user's structured audit_logs rows into a plain-English summary
 * for an admin, via the Gemini API (free tier). Reuses AuditService::paginate()
 * (already supports subject_id/subject_type/date_from filtering) instead of
 * adding a new query path — this service only adds prompt-building + the
 * HTTP call on top of data that was already fetchable.
 */
final readonly class AuditAiSummaryService
{
    private const MAX_ROWS = 100;

    private const REQUEST_TIMEOUT_SECONDS = 20;

    public function __construct(
        private AuditService $auditService,
    ) {}

    public function summarizeForUser(User $user, int $days = 30): string
    {
        $apiKey = config('services.gemini.api_key');

        if (blank($apiKey)) {
            throw AiSummaryUnavailableException::apiKeyMissing();
        }

        $logs = $this->auditService->paginate(
            new AuditLogFilterDTO(
                subjectId: $user->id,
                subjectType: User::class,
                dateFrom: now()->subDays($days)->toDateTimeString(),
                perPage: self::MAX_ROWS,
            ),
        );

        if ($logs->isEmpty()) {
            throw AiSummaryUnavailableException::noActivity();
        }

        $prompt = $this->buildPrompt($user, $logs->items(), $days);

        return $this->callGemini($prompt, (string) $apiKey);
    }

    /**
     * @param  array<int, AuditLog>  $logs
     */
    private function buildPrompt(User $user, array $logs, int $days): string
    {
        $lines = array_map(
            static fn (AuditLog $log): string => sprintf(
                '- %s | %s | severity=%s | status=%s | %s',
                $log->occurred_at->toDateTimeString(),
                $log->action->value,
                $log->severity->value,
                $log->status,
                $log->description ?? '',
            ),
            $logs,
        );

        return sprintf(
            "You are reviewing the account activity log for user #%d (%s) over the last %d days, ".
            "for an admin who needs a quick, plain-English summary, not a security report full of jargon.\n\n".
            "Rules:\n".
            "- 3 to 6 sentences, plain language.\n".
            "- Call out anything that looks unusual or risky (multiple failed logins, account locks, ".
            "logins from different places close together, repeated password/2FA changes).\n".
            "- If nothing looks unusual, say so plainly. Do not invent concern.\n".
            "- Group and summarize; do not repeat every single row.\n\n".
            "Activity log (oldest to newest):\n%s",
            $user->id,
            $user->email,
            $days,
            implode("\n", array_reverse($lines)),
        );
    }

    // Gemini's free tier needs no billing to be set up — see the Audit AI
    // summary "which provider" decision. Key goes in the x-goog-api-key
    // header (Google's current documented method), never in the URL, so it
    // can never end up logged by a proxy that records query strings.
    private function callGemini(string $prompt, string $apiKey): string
    {
        $model = config('services.gemini.model');

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
            'content-type' => 'application/json',
        ])
            ->timeout(self::REQUEST_TIMEOUT_SECONDS)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 2000,
                        // Plain summarization doesn't need extended
                        // reasoning — disabling it also stops "thinking"
                        // tokens from eating into maxOutputTokens and
                        // truncating the actual visible answer (a known
                        // issue with newer Gemini "thinking" models).
                        'thinkingConfig' => [
                            'thinkingBudget' => 0,
                        ],
                    ],
                ],
            );

        if ($response->failed()) {
            Log::error('[Audit][AI] Gemini API call failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw AiSummaryUnavailableException::providerError(
                'HTTP '.$response->status(),
            );
        }

        $finishReason = $response->json('candidates.0.finishReason');

        if ($finishReason === 'MAX_TOKENS') {
            // Not a hard failure — some text may still have come back — but
            // worth a loud log line, since a MAX_TOKENS finish means the
            // summary was cut off mid-sentence (exactly what happened
            // before thinkingBudget was set to 0). If this shows up again,
            // maxOutputTokens needs raising further.
            Log::warning('[Audit][AI] Gemini response was truncated (MAX_TOKENS).', [
                'model' => $model,
            ]);
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            throw AiSummaryUnavailableException::providerError(
                'Empty response from provider.',
            );
        }

        return $text;
    }
}
