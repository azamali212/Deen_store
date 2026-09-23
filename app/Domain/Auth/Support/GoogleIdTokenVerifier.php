<?php

declare(strict_types=1);

namespace App\Domain\Auth\Support;

use App\Domain\Auth\Contracts\GoogleTokenVerifierInterface;
use App\Domain\Auth\DTO\GoogleProfileDTO;
use App\Domain\Auth\Exceptions\InvalidSocialTokenException;
use Google\Client as GoogleClient;
use Throwable;

/**
 * Real implementation, backed by Google's own official PHP client
 * (google/apiclient). verifyIdToken() does the actual cryptographic work:
 * checks the token's signature against Google's published public keys,
 * checks it hasn't expired, and checks it was issued for OUR app
 * (GOOGLE_CLIENT_ID as the "audience") — not some other app that also
 * happens to use "Sign in with Google".
 *
 * We never parse or trust the token ourselves; Google's library is the
 * one piece of code allowed to decide a token is genuine.
 */
final class GoogleIdTokenVerifier implements GoogleTokenVerifierInterface
{
    public function verify(string $idToken): GoogleProfileDTO
    {
        $clientId = config('services.google.client_id');

        if (blank($clientId)) {
            throw InvalidSocialTokenException::invalid();
        }

        $client = new GoogleClient(['client_id' => $clientId]);

        try {
            $payload = $client->verifyIdToken($idToken);
        } catch (Throwable) {
            throw InvalidSocialTokenException::invalid();
        }

        // verifyIdToken() returns `false` (not an exception) for a token
        // that parses fine but fails validation — expired, wrong audience,
        // wrong issuer, bad signature, etc.
        if ($payload === false) {
            throw InvalidSocialTokenException::invalid();
        }

        if (! isset($payload['sub'], $payload['email'])) {
            throw InvalidSocialTokenException::invalid();
        }

        return new GoogleProfileDTO(
            googleId: (string) $payload['sub'],
            email: strtolower((string) $payload['email']),
            emailVerified: (bool) ($payload['email_verified'] ?? false),
            name: isset($payload['name']) ? (string) $payload['name'] : null,
        );
    }
}
