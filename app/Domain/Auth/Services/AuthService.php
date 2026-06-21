<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTO\AuthResult;
use App\Domain\Auth\DTO\RegisterAdminDTO;
use App\Domain\Auth\DTO\RegisterCustomerDTO;
use App\Domain\Auth\DTO\RegisterSellerDTO;
use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\LogoutDTO;
use App\Domain\Auth\Enums\AuthStatus;
use App\Domain\Auth\Enums\LoginRiskLevel;
use App\Domain\Auth\Enums\OtpPurpose;
use App\Domain\Auth\Exceptions\AccountInactiveException;
use App\Domain\Auth\Exceptions\InvalidCredentialsException;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreateLoginLogData;
use App\Domain\Auth\Repositories\DTO\CreateSessionData;
use App\Domain\Auth\Repositories\DTO\CreateUserData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
    ) {}

    public function login(LoginDTO $dto): AuthResult
    {
        $user = $this->repository->findUserByEmail(
            $dto->email
        );

        if (! $user instanceof User) {

            $this->logFailedLogin(
                $dto,
                'User not found'
            );

            throw new InvalidCredentialsException();
        }

        if (! Hash::check(
            $dto->password,
            $user->password
        )) {

            $this->logFailedLogin(
                $dto,
                'Invalid password'
            );

            throw new InvalidCredentialsException();
        }

        if (! $user->isActive()) {
            throw new AccountInactiveException();
        }

        $this->panelAccessService
            ->ensureCanAccess(
                $user,
                $dto->panel
            );

            $fingerprint = $this->deviceFingerprintService

            ->generate(
                $dto->ipAddress,
                $dto->userAgent,
                $dto->deviceName,
                $dto->panel->value
            );

            $riskScore = $this->riskScoringService
            ->calculate(
                user: $user,
                trustedDevice: false,
                newIp: false,
                newCountry: false,
                failedAttempts: 0,
            );

        if ($riskScore >= 70) {

            $this->otpService->create(
                $user,
                OtpPurpose::LOGIN
            );

            return new AuthResult(
                user: $user,
                token: '',
                tokenName: '',
                sessionId: null,
                abilities: [],
                accessiblePanels: [],
                requiresOtp: true,
                message: 'OTP verification required.'
            );
        }

        $tokenName = $dto->panel->value.'-panel';

        $token = $user
            ->createToken(
                $tokenName,
                ['*']
            );

            $session = $this->sessionService->create(

                new CreateSessionData(
                    userId: (string) $user->id,
                    tokenId: (string) $token->accessToken->id,
                    panel: $dto->panel,
                    ipAddress: $dto->ipAddress,
                    userAgent: $dto->userAgent,
                    deviceName: $dto->deviceName,
                )
            
            );

        $this->repository->updateUser(
            $user,
            [
                'last_login_at' => now(),
                'last_login_ip' => $dto->ipAddress,
            ]
        );

        $this->repository->createLoginLog(
            new CreateLoginLogData(
                status: AuthStatus::SUCCESS,
                panel: $dto->panel,
                provider: $dto->provider,
                riskLevel: $this->riskScoringService->level($riskScore),
                userId: (string) $user->id,
                email: $user->email,
                ipAddress: $dto->ipAddress,
                userAgent: $dto->userAgent,
                deviceName: $dto->deviceName,
            )
        );

        return new AuthResult(
            user: $user,
            token: $token->plainTextToken,
            tokenName: $tokenName,
            sessionId: (string) $session->id,
            abilities: ['*'],
            accessiblePanels: $this->panelAccessService
                ->accessiblePanels($user),
        );
    }

    public function logout(
        User $user,
        LogoutDTO $dto
    ): void {

        if ($dto->logoutAllDevices) {

            $this->logoutAllDevices($user);

            return;
        }

        if ($dto->tokenId !== null) {

            $this->repository
                ->terminateSession(
                    $dto->tokenId
                );
        }
    }

    public function logoutAllDevices(
        User $user
    ): void {

        $this->repository
            ->terminateAllSessions(
                $user->id
            );

        $user->tokens()
            ->delete();
    }

    public function verifyOtp(
        User $user,
        string $code,
        OtpPurpose $purpose
    ): bool {

        return $this->otpService
            ->verify(
                $user,
                $code,
                $purpose
            );
    }

    private function logFailedLogin(
        LoginDTO $dto,
        string $reason
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
            )
        );
    }

    public function registerCustomer(
        RegisterCustomerDTO $dto
    ): User {
    
        return DB::transaction(
            function () use ($dto): User {
    
                $user = $this->repository->createUser(
                    new CreateUserData(
                        name: $dto->name,
                        email: $dto->email,                    
                        passwordHash: Hash::make(                   
                            $dto->password
                        ),
                        phone: $dto->phone,
                    
                    )
                );
    
                $user->assignRole(
                    'customer'
                );
    
                $this->repository->createLoginLog(
                    new CreateLoginLogData(
                        status: AuthStatus::SUCCESS,
                        panel: \App\Domain\Auth\Enums\AuthPanel::CUSTOMER,
                        provider: \App\Domain\Auth\Enums\LoginProvider::PASSWORD,
                        riskLevel: LoginRiskLevel::LOW,
                        userId: (string) $user->id,
                        email: $user->email,
                        ipAddress: $dto->ipAddress,
                        userAgent: $dto->userAgent,
                        deviceName: 'registration',
                    )
                );
    
                return $user;
            }
        );
    }

    public function registerSeller(
        RegisterSellerDTO $dto
    ): User {
    
        return DB::transaction(
            function () use ($dto): User {
    
                $user = $this->repository->createUser(
                    new CreateUserData(
                        name: $dto->name,
                        email: $dto->email,
                        passwordHash: Hash::make(
                            $dto->password
                        ),
                        phone: $dto->phone,
                    )
                );
    
                $user->assignRole(
                    'seller'
                );
    
                $this->repository->createLoginLog(
                    new CreateLoginLogData(
                        status: AuthStatus::SUCCESS,
                        panel: \App\Domain\Auth\Enums\AuthPanel::SELLER,
                        provider: \App\Domain\Auth\Enums\LoginProvider::PASSWORD,
                        riskLevel: LoginRiskLevel::LOW,
                        userId: (string) $user->id,
                        email: $user->email,
                        ipAddress: $dto->ipAddress,
                        userAgent: $dto->userAgent,
                        deviceName: $dto->storeName,
                        metadata: [
                            'store_name' => $dto->storeName,
                            'business_name' => $dto->businessName,
                            'business_type' => $dto->businessType,
                        ],
                    )
                );
    
                return $user;
            }
        );
    }

    public function registerAdmin(
        RegisterAdminDTO $dto
    ): User {
    
        return DB::transaction(
            function () use ($dto): User {
    
                $user = $this->repository->createUser(
                    new CreateUserData(
                        name: $dto->name,
                        email: $dto->email,
                        passwordHash: Hash::make(
                            $dto->password
                        ),
                        phone: $dto->phone,
                    )
                );
    
                $user->assignRole(
                    $dto->role->value
                );
    
                $this->repository->createLoginLog(
                    new CreateLoginLogData(
                        status: AuthStatus::SUCCESS,
                        panel: \App\Domain\Auth\Enums\AuthPanel::ADMIN,
                        provider: \App\Domain\Auth\Enums\LoginProvider::PASSWORD,
                        riskLevel: LoginRiskLevel::LOW,
                        userId: (string) $user->id,
                        email: $user->email,
                        ipAddress: $dto->ipAddress,
                        userAgent: $dto->userAgent,
                        deviceName: 'admin-registration',
                        metadata: [
                            'created_by' => $dto->createdByUserId,
                            'role' => $dto->role->value,
                        ],
                    )
                );
    
                return $user;
            }
        );
    }
}