<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

/**
 * The verified, trustworthy identity claims pulled out of a Google ID
 * token — nothing here reaches the rest of the app until
 * GoogleTokenVerifierInterface::verify() has cryptographically confirmed
 * the token was actually issued by Google for OUR app.
 */
final readonly class GoogleProfileDTO
{
    public function __construct(
        public string $googleId,
        public string $email,
        public bool $emailVerified,
        public ?string $name,
    ) {}
}
