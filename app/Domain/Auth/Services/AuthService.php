<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTO\AuthResult;
use App\Domain\Auth\DTO\CreateUserDTO;
use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\LoginRateLimitDTO;
use App\Domain\Auth\DTO\LogoutDTO;
use App\Domain\Auth\DTO\VerifyOtpDTO;
use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\AuthStatus;
use App\Domain\Auth\Enums\LoginProvider;
use App\Domain\Auth\Enums\LoginRiskLevel;
use App\Domain\Auth\Enums\OtpPurpose;
use App\Domain\Auth\Events\Data\LoginEventData;
use App\Domain\Auth\Events\UserCreated;
use App\Domain\Auth\Events\UserLoggedIn;
use App\Domain\Auth\Exceptions\AccountInactiveException;
use App\Domain\Auth\Exceptions\InvalidCredentialsException;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreateLoginLogData;
use App\Domain\Auth\Repositories\DTO\CreateSessionData;
use App\Domain\Auth\Repositories\DTO\CreateTrustedDeviceData;
use App\Domain\Auth\Repositories\DTO\CreateUserData;
use App\Domain\Permissions\Enums\SystemRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final readonly class AuthService
{
    public function __construct(
        private AuthRepositoryInterface $repository,
        private PanelAccessService $panelAccessService,
        private SessionService $sessionService,
        private OtpService $otpService,
        private DeviceFingerprintService $deviceFingerprintService,
        private SuspiciousLoginService $suspiciousLoginService,
        private AuthRiskScoringService $riskScoringService,
        private LoginRateLimitService $loginRateLimitService,
        private AccountLockoutService $accountLockoutService,
    ) {}

    public function login(LoginDTO $dto): AuthResult
    {
        $rateLimit = LoginRateLimitDTO::make(
            email: $dto->email,
            panel: $dto->panel,
            ipAddress: $dto->ipAddress,
        );

        $this->loginRateLimitService
            ->ensureIsNotLimited(
                $rateLimit,
            );
        $user = $this->repository->findUserByEmail(
            $dto->email,
        );

        if (! $user instanceof User) {
            $this->loginRateLimitService
                ->hit(
                    $rateLimit,
                );
            $this->logFailedLogin(
                $dto,
                'User not found',
            );

            throw new InvalidCredentialsException;
        }

        $this->accountLockoutService
            ->ensureNotLocked(
                $user,
            );

        if (! Hash::check(
            $dto->password,
            $user->password,
        )) {
            $this->loginRateLimitService
                ->hit(
                    $rateLimit,
                );
            $this->accountLockoutService
                ->recordFailedAttempt(
                    $user,
                );
            $this->logFailedLogin(
                $dto,
                'Invalid password',
            );

            throw new InvalidCredentialsException;
        }

        $this->accountLockoutService
            ->resetFailedAttempts(
                $user,
            );

        if (! $user->isActive()) {
            throw new AccountInactiveException;
        }

        $this->panelAccessService
            ->ensureCanAccess(
                $user,
                $dto->panel,
            );

        $riskScore = $this->riskScoringService
            ->calculate(
                user: $user,
                trustedDevice: false,
                newIp: false,
                newCountry: false,
                failedAttempts: 0,
            );

        $purpose = $dto->panel === AuthPanel::ADMIN
            ? OtpPurpose::ADMIN_LOGIN
            : OtpPurpose::LOGIN;

        $this->otpService->create(
            $user,
            $purpose,
        );

        $this->loginRateLimitService
            ->clear(
                $rateLimit,
            );

        return new AuthResult(
            user: $user,
            token: '',
            tokenName: '',
            sessionId: null,
            abilities: [],
            accessiblePanels: [],
            requiresOtp: true,
            requiresStepUp: $riskScore >= 70,
            message: 'OTP has been sent to your email address.',
        );
    }

    public function logout(
        User $user,
        LogoutDTO $dto,
    ): void {

        if ($dto->logoutAllDevices) {

            $this->logoutAllDevices($user);

            return;
        }

        if ($dto->tokenId !== null) {

            $this->repository
                ->terminateSession(
                    $dto->tokenId,
                );
        }
    }

    public function logoutAllDevices(
        User $user,
    ): void {

        $this->repository
            ->terminateAllSessions(
                $user->id,
            );

        $user->tokens()
            ->delete();
    }

    public function verifyOtp(
        VerifyOtpDTO $dto,
    ): AuthResult {

        $rateLimit = LoginRateLimitDTO::make(
            email: $dto->identifier,
            panel: $dto->panel,
            ipAddress: $dto->ipAddress,
        );

        $this->loginRateLimitService
            ->ensureIsNotLimited(
                $rateLimit,
            );

        $user = $this->repository
            ->findUserByEmail(
                $dto->identifier,
            );

        if (! $user instanceof User) {
            $this->loginRateLimitService
                ->hit(
                    $rateLimit,
                );

            throw new InvalidCredentialsException;
        }

        $this->panelAccessService
            ->ensureCanAccess(
                $user,
                $dto->panel,
            );

        $verified = $this->otpService
            ->verify(
                $user,
                $dto->code,
                $dto->purpose,
            );

        if (! $verified) {
            $this->loginRateLimitService
                ->hit(
                    $rateLimit,
                );

            throw new RuntimeException(
                'Invalid or expired OTP.',
            );
        }

        $this->loginRateLimitService
            ->clear(
                $rateLimit,
            );

        $deviceName = $dto->deviceName
            ?? 'unknown-device';

        $fingerprint = $this->deviceFingerprintService
            ->generate(
                ipAddress: $dto->ipAddress,
                userAgent: $dto->userAgent,
                deviceName: $deviceName,
                panel: $dto->panel->value,
            );
        $tokenName = $dto->panel->value.'-panel';

        $token = $user->createToken(
            $tokenName,
            ['*'],
        );

        $session = $this->sessionService->create(
            new CreateSessionData(
                userId: (string) $user->id,
                tokenId: (string) $token->accessToken->id,
                panel: $dto->panel,
                ipAddress: $dto->ipAddress,
                userAgent: $dto->userAgent,
                deviceName: $deviceName,
            ),
        );
        $this->repository->saveTrustedDevice(
            new CreateTrustedDeviceData(
                userId: (string) $user->id,
                fingerprint: $fingerprint,
                deviceName: $deviceName,
                ipAddress: $dto->ipAddress,
                userAgent: $dto->userAgent,
                trustedUntil: now()->addDays(30),
            ),
        );
        $this->repository->updateUser(
            $user,
            [
                'last_login_at' => now(),
                'last_login_ip' => $dto->ipAddress,
            ],
        );

        $this->repository->createLoginLog(

            new CreateLoginLogData(
                status: AuthStatus::SUCCESS,
                panel: $dto->panel,
                provider: $dto->provider,
                riskLevel: LoginRiskLevel::LOW,
                userId: (string) $user->id,
                email: $user->email,
                ipAddress: $dto->ipAddress,
                userAgent: $dto->userAgent,
                deviceName: $deviceName,
            ),
        );
        event(
            new UserLoggedIn(
                new LoginEventData(
                    userId: (string) $user->id,
                    email: $user->email,
                    panel: $dto->panel,
                    provider: $dto->provider,
                    ipAddress: $dto->ipAddress ?? '',
                    userAgent: $dto->userAgent,
                    deviceName: $deviceName,
                    browser: null,
                    operatingSystem: null,
                    riskScore: 0,
                    occurredAt: now(),
                ),
            ),

        );

        return new AuthResult(
            user: $user,
            token: $token->plainTextToken,
            tokenName: $tokenName,
            sessionId: (string) $session->id,
            abilities: ['*'],
            accessiblePanels: $this->panelAccessService->accessiblePanels($user),
            requiresOtp: false,
            requiresStepUp: false,
            message: 'Login successful.',
        );
    }

    private function logFailedLogin(
        LoginDTO $dto,
        string $reason,
    ): void {

        $this->repository->createLoginLog(
            new CreateLoginLogData(
                status: AuthStatus::FAILED,
                panel: $dto->panel,
                provider: $dto->provider,
                riskLevel: LoginRiskLevel::LOW,
                email: $dto->email,
                ipAddress: $dto->ipAddress,
                userAgent: $dto->userAgent,
                deviceName: $dto->deviceName,
                failureReason: $reason,
            ),
        );
    }

    public function createUser(
        CreateUserDTO $dto,
    ): User {
        return DB::transaction(
            function () use ($dto): User {
                $user = $this->repository->createUser(
                    new CreateUserData(
                        name: $dto->name,
                        email: $dto->email,
                        passwordHash: Hash::make(
                            $dto->password,
                        ),
                        phone: $dto->phone,
                    ),
                );
                $user->assignRole(
                    $dto->role->value,
                );
                $this->repository->createLoginLog(
                    new CreateLoginLogData(
                        status: AuthStatus::SUCCESS,
                        panel: $this->resolvePanel(
                            $dto->role,
                        ),
                        provider: LoginProvider::PASSWORD,
                        riskLevel: LoginRiskLevel::LOW,
                        userId: (string) $user->id,
                        email: $user->email,
                        ipAddress: $dto->ipAddress,
                        userAgent: $dto->userAgent,
                        deviceName: 'user-created',
                        metadata: [
                            'created_by' => $dto->createdByUserId,
                            'role' => $dto->role->value,
                        ],
                    ),
                );
                event(
                    new UserCreated(
                        user: $user,
                        createdBy: $dto->createdByUserId,
                    ),
                );

                // dd('UserCreated Event Fired');
                return $user;
            },
        );
    }

    private function resolvePanel(
        SystemRole $role,
    ): AuthPanel {
        return match ($role) {
            SystemRole::CUSTOMER => AuthPanel::CUSTOMER,

            SystemRole::SELLER,
            SystemRole::SELLER_MANAGER,
            SystemRole::SELLER_STAFF => AuthPanel::SELLER,

            default => AuthPanel::ADMIN,
        };
    }
}