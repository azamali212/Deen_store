<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Auth\Contracts\GoogleTokenVerifierInterface;
use App\Domain\Auth\DTO\GoogleProfileDTO;
use App\Domain\Auth\Exceptions\InvalidSocialTokenException;

/**
 * Test double for GoogleTokenVerifierInterface — never calls Google, just
 * hands back whichever GoogleProfileDTO the test configured (or throws,
 * simulating an invalid/expired token), so the real prompt-building and
 * account-resolution code in AuthService still runs and gets exercised.
 */
final class FakeGoogleTokenVerifier implements GoogleTokenVerifierInterface
{
    public function __construct(
        private readonly ?GoogleProfileDTO $profile,
    ) {}

    public function verify(string $idToken): GoogleProfileDTO
    {
        if ($this->profile === null) {
            throw InvalidSocialTokenException::invalid();
        }

        return $this->profile;
    }
}
