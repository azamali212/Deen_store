<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Services;

use App\Domain\Moderation\DTO\ModerationCheckResultDTO;
use App\Domain\Moderation\Enums\ModerationSeverity;
use App\Domain\Moderation\Exceptions\ModerationCheckFailedException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class ProfileModerationAiService
{
    private const REQUEST_TIMEOUT_SECONDS = 20;

    /**
     * Checks profile text fields BEFORE they are saved. Called synchronously
     * from UpdateProfileAction — the request blocks until this returns.
     *
     * @param  array<string, string|null>  $textFields
     */
    public function checkProfileText(array $textFields): ModerationCheckResultDTO
    {
        $lines = [];

        foreach ($textFields as $field => $value) {
            $lines[] = sprintf('- %s: "%s"', $field, $value ?? '(empty)');
        }

        $prompt = sprintf(
            "You are a content moderation reviewer for an e-commerce platform's user profiles.\n\n".
            "Review the following profile fields for policy violations: hate speech, harassment, ".
            "profanity or vulgar/abusive language, sexual or nude content, violence/gore, spam or scam ".
            "patterns, impersonation, or exposed personal contact information (phone numbers, emails, ".
            "external chat handles) used to bypass the platform.\n\n".
            "Profile fields:\n%s\n\n".
            "%s",
            implode("\n", $lines),
            $this->responseFormatInstructions(),
        );

        return $this->callGemini([['text' => $prompt]]);
    }

    /**
     * Checks a candidate avatar image BEFORE it is stored. Called
     * synchronously from UploadAvatarAction, straight off the uploaded
     * file's bytes — nothing is written to disk until this comes back clean.
     */
    public function checkAvatarImage(string $bytes, string $mimeType): ModerationCheckResultDTO
    {
        $prompt = sprintf(
            "You are a content moderation reviewer for an e-commerce platform's user profile avatars.\n\n".
            "Review the attached image for policy violations: nudity or sexual content, violence/gore, ".
            "hate symbols, or content that impersonates another real person or a brand/logo it has no ".
            "right to use. A normal photo, illustration, logo, or abstract image is NOT a violation.\n\n".
            "%s",
            $this->responseFormatInstructions(fieldNameForImage: 'avatar'),
        );

        return $this->callGemini([
            ['text' => $prompt],
            ['inlineData' => ['mimeType' => $mimeType, 'data' => base64_encode($bytes)]],
        ]);
    }

    private function responseFormatInstructions(?string $fieldNameForImage = null): string
    {
        $fieldNote = $fieldNameForImage !== null
            ? sprintf('Use "%s" as the field name in flagged_fields if the image is the problem.'."\n", $fieldNameForImage)
            : '';

        return "Respond with ONLY valid JSON in this exact shape, no other text:\n".
            '{"clean": true|false, "severity": "low"|"medium"|"high"|null, '.
            '"summary": "1-2 plain-English sentences for an admin", '.
            '"flagged_fields": {"<field_name>": {"category": "...", "reason": "..."}}}'."\n\n".
            "Rules:\n".
            "- If nothing violates policy, return clean=true, severity=null, flagged_fields={}, and a ".
            "short summary saying it looks fine.\n".
            "- Only include a field in flagged_fields if THAT field is the problem.\n".
            $fieldNote.
            "- Be conservative on borderline cases, but treat clear profanity or explicit sexual wording ".
            "as a violation even without a specific target.\n";
    }

    /**
     * @param  array<int, array<string, mixed>>  $parts
     */
    private function callGemini(array $parts): ModerationCheckResultDTO
    {
        $apiKey = config('services.gemini.api_key');

        if (blank($apiKey)) {
            throw ModerationCheckFailedException::apiKeyMissing();
        }

        $model = config('services.gemini.model');

        $response = Http::withHeaders([
            'x-goog-api-key' => (string) $apiKey,
            'content-type' => 'application/json',
        ])
            ->timeout(self::REQUEST_TIMEOUT_SECONDS)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                [
                    'contents' => [['parts' => $parts]],
                    'generationConfig' => [
                        'maxOutputTokens' => 1024,
                        'responseMimeType' => 'application/json',
                        'thinkingConfig' => ['thinkingBudget' => 0],
                    ],
                ],
            );

        if ($response->failed()) {
            Log::error('[Moderation][AI] Gemini API call failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ModerationCheckFailedException::providerError('HTTP '.$response->status());
        }

        $finishReason = $response->json('candidates.0.finishReason');

        if ($finishReason === 'MAX_TOKENS') {
            Log::warning('[Moderation][AI] Gemini response was truncated (MAX_TOKENS).', ['model' => $model]);
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            throw ModerationCheckFailedException::providerError('Empty response from provider.');
        }

        return $this->parseResult($text);
    }

    private function parseResult(string $json): ModerationCheckResultDTO
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded) || ! isset($decoded['clean'])) {
            Log::error('[Moderation][AI] Could not parse Gemini JSON response.', ['raw' => $json]);

            throw ModerationCheckFailedException::providerError('Unparseable JSON response.');
        }

        if ($decoded['clean'] === true) {
            return ModerationCheckResultDTO::clean(
                (string) ($decoded['summary'] ?? 'No issues found.'),
            );
        }

        $severity = ModerationSeverity::tryFrom((string) ($decoded['severity'] ?? 'low'))
            ?? ModerationSeverity::LOW;

        /** @var array<string, array{category: string, reason: string}> $flaggedFields */
        $flaggedFields = is_array($decoded['flagged_fields'] ?? null) ? $decoded['flagged_fields'] : [];

        return ModerationCheckResultDTO::flagged(
            severity: $severity,
            flaggedFields: $flaggedFields,
            summary: (string) ($decoded['summary'] ?? 'Content was flagged for review.'),
        );
    }
}
