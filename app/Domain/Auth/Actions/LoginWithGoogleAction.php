<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Contracts\GoogleTokenVerifierInterface;
use App\Domain\Auth\DTO\AuthResult;
use App\Domain\Auth\Services\AuthService;

final readonly class LoginWithGoogleAction
{
    public function __construct(
        private GoogleTokenVerifierInterface $tokenVerifier,
        private AuthService $authService,
    ) {}

    public function execute(
        string $idToken,
        string $ipAddress,
        ?string $userAgent,
        ?string $deviceName,
    ): AuthResult {

        $profile = $this->tokenVerifier->verify($idToken);

        return $this->authService->loginWithGoogle(
            $profile,
            $ipAddress,
            $userAgent,
            $deviceName,
        );
    }
}
