<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\DTO\GoogleProfileDTO;
use App\Domain\Auth\Exceptions\InvalidSocialTokenException;

/**
 * Verifies a Google "Sign in with Google" ID token and returns the
 * identity Google is vouching for.
 *
 * This is deliberately an interface, not a direct call to Google's SDK,
 * for the same reason SmsGatewayInterface/AvatarStorageInterface are
 * interfaces: it lets tests bind a fake implementation instead of making
 * a real network call to Google on every test run.
 */
interface GoogleTokenVerifierInterface
{
    /**
     * @throws InvalidSocialTokenException if the token is malformed, expired,
     *                                      signed by someone other than Google,
     *                                      or was not issued for this app.
     */
    public function verify(string $idToken): GoogleProfileDTO;
}
