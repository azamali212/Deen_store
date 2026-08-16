<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTO\AuthResult;
use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\AuthStatus;
use App\Domain\Auth\Enums\LoginProvider;
use App\Domain\Auth\Enums\LoginRiskLevel;
use App\Domain\Auth\Events\Data\LoginEventData;
use App\Domain\Auth\Events\UserLoggedIn;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreateLoginLogData;
use App\Domain\Auth\Repositories\DTO\CreateSessionData;
use App\Domain\Auth\Repositories\DTO\CreateTrustedDeviceData;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

final readonly class LoginCompletionService
{
    public function __construct(
        private AuthRepositoryInterface $repository,
        private SessionService $sessionService,
        private DeviceFingerprintService $deviceFingerprintService,
        private PanelAccessService $panelAccessService,
    ) {}

    public function complete(
        User $user,
        AuthPanel $panel,
        LoginProvider $provider,
        string $ipAddress,
        ?string $userAgent,
        ?string $deviceName,
    ): AuthResult {

        $deviceName ??= 'unknown-device';

        $token = $this->createToken(
            $user,
            $panel,
        );

        $session = $this->createSession(
            $user,
            $token,
            $panel,
            $ipAddress,
            $userAgent,
            $deviceName,
        );

        $this->storeTrustedDevice(
            $user,
            $panel,
            $ipAddress,
            $userAgent,
            $deviceName,
        );

        $this->updateLastLogin(
            $user,
            $ipAddress,
        );

        $this->logSuccessfulLogin(
            $user,
            $panel,
            $provider,
            $ipAddress,
            $userAgent,
            $deviceName,
        );

        $this->dispatchLoginEvent(
            $user,
            $panel,
            $provider,
            $ipAddress,
            $userAgent,
            $deviceName,
        );

        return $this->buildAuthResult(
            $user,
            $token,
            $session,
            $panel,
        );
    }

    private function createToken(
        User $user,
        AuthPanel $panel,
    ): NewAccessToken {

        return $user->createToken(
            $panel->value.'-panel',
            ['*'],
        );
    }

    private function createSession(
        User $user,
        NewAccessToken $token,
        AuthPanel $panel,
        string $ipAddress,
        ?string $userAgent,
        string $deviceName,
    ) {
        return $this->sessionService
            ->create(
                new CreateSessionData(
                    userId: (string) $user->id,
                    tokenId: (string) $token->accessToken->id,
                    panel: $panel,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    deviceName: $deviceName,
                ),
            );
    }

    private function storeTrustedDevice(
        User $user,
        AuthPanel $panel,
        string $ipAddress,
        ?string $userAgent,
        string $deviceName,
    ): void {

        $fingerprint = $this->deviceFingerprintService
            ->generate(
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceName: $deviceName,
                panel: $panel->value,
            );

        $this->repository
            ->saveTrustedDevice(
                new CreateTrustedDeviceData(
                    userId: (string) $user->id,
                    fingerprint: $fingerprint,
                    deviceName: $deviceName,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    trustedUntil: now()->addDays(30),
                ),
            );
    }

    private function updateLastLogin(
        User $user,
        string $ipAddress,
    ): void {

        $this->repository
            ->updateUser(
                $user,
                [
                    'last_login_at' => now(),
                    'last_login_ip' => $ipAddress,
                ],
            );
    }

    private function logSuccessfulLogin(
        User $user,
        AuthPanel $panel,
        LoginProvider $provider,
        string $ipAddress,
        ?string $userAgent,
        string $deviceName,
    ): void {

        $this->repository
            ->createLoginLog(
                new CreateLoginLogData(
                    status: AuthStatus::SUCCESS,
                    panel: $panel,
                    provider: $provider,
                    riskLevel: LoginRiskLevel::LOW,
                    userId: (string) $user->id,
                    email: $user->email,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    deviceName: $deviceName,
                ),
            );
    }

    private function dispatchLoginEvent(
        User $user,
        AuthPanel $panel,
        LoginProvider $provider,
        string $ipAddress,
        ?string $userAgent,
        string $deviceName,
    ): void {

        event(
            new UserLoggedIn(
                new LoginEventData(
                    userId: (string) $user->id,
                    email: $user->email,
                    panel: $panel,
                    provider: $provider,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    deviceName: $deviceName,
                    browser: null,
                    operatingSystem: null,
                    riskScore: 0,
                    occurredAt: now(),
                ),
            ),
        );
    }

    private function buildAuthResult(
        User $user,
        NewAccessToken $token,
        mixed $session,
        AuthPanel $panel,
    ): AuthResult {

        return new AuthResult(
            user: $user,
            token: $token->plainTextToken,
            tokenName: $panel->value.'-panel',
            sessionId: (string) $session->id,
            abilities: ['*'],
            accessiblePanels: $this->panelAccessService
                ->accessiblePanels(
                    $user,
                ),
            requiresOtp: false,
            requiresStepUp: false,
            requiresTwoFactor: false,
            message: 'Login successful.',
        );
    }
}
