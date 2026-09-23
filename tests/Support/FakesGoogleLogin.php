<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Auth\Contracts\GoogleTokenVerifierInterface;
use App\Domain\Auth\DTO\GoogleProfileDTO;

trait FakesGoogleLogin
{
    /**
     * Binds a fake Google verifier that returns a chosen profile,
     * regardless of what id_token string the test actually sends.
     */
    protected function fakeGoogleLogin(array $overrides = []): GoogleProfileDTO
    {
        $profile = new GoogleProfileDTO(
            googleId: $overrides['google_id'] ?? (string) fake()->unique()->numerify('##################'),
            email: $overrides['email'] ?? fake()->unique()->safeEmail(),
            emailVerified: $overrides['email_verified'] ?? true,
            name: $overrides['name'] ?? 'Google Test User',
        );

        $this->app->bind(
            GoogleTokenVerifierInterface::class,
            fn () => new FakeGoogleTokenVerifier($profile),
        );

        return $profile;
    }

    /**
     * Binds a fake Google verifier that always rejects the token —
     * simulates an expired/forged/wrong-audience ID token.
     */
    protected function fakeInvalidGoogleToken(): void
    {
        $this->app->bind(
            GoogleTokenVerifierInterface::class,
            fn () => new FakeGoogleTokenVerifier(null),
        );
    }
}
