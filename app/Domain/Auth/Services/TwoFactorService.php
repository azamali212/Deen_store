<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTO\AuthResult;
use App\Domain\Auth\DTO\ConfirmTwoFactorDTO;
use App\Domain\Auth\DTO\DisableTwoFactorDTO;
use App\Domain\Auth\DTO\EnableTwoFactorDTO;
use App\Domain\Auth\DTO\VerifyTwoFactorDTO;
use App\Domain\Auth\Enums\TwoFactorProvider;
use App\Domain\Auth\Events\RecoveryCodesGenerated;
use App\Domain\Auth\Events\TwoFactorConfirmed;
use App\Domain\Auth\Events\TwoFactorDisabled;
use App\Domain\Auth\Events\TwoFactorEnabled;
use App\Domain\Auth\Events\TwoFactorVerified;
use App\Domain\Auth\Exceptions\InvalidTwoFactorCodeException;
use App\Domain\Auth\Exceptions\TwoFactorAlreadyEnabledException;
use App\Domain\Auth\Exceptions\TwoFactorNotEnabledException;
use App\Domain\Auth\Repositories\Contracts\TwoFactorRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\UpdateTwoFactorData;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final readonly class TwoFactorService
{
    public function __construct(
        private TwoFactorRepositoryInterface $repository,
        private TotpService $totpService,
        private TwoFactorSecretService $secretService,
        private RecoveryCodeService $recoveryCodeService,
        private LoginCompletionService $loginCompletionService,
    ) {}

    public function enable(
        EnableTwoFactorDTO $dto,
    ): array {

        $user = $this->repository
            ->findUserById(
                $dto->userId,
            );

        if (! $user) {
            throw new RuntimeException(
                'User not found.',
            );
        }

        if (
            $this->repository->existsEnabled(
                $user,
            )
        ) {
            throw new TwoFactorAlreadyEnabledException;
        }

        $plainSecret = $this->totpService
            ->generateSecret();

        $encryptedSecret = $this->secretService
            ->encrypt(
                $plainSecret,
            );

        $this->repository
            ->updateTwoFactor(
                $user,
                new UpdateTwoFactorData(
                    enabled: false,
                    provider: $dto->provider,
                    secret: $encryptedSecret,
                ),
            );

        event(
            new TwoFactorEnabled(
                $user,
            ),
        );

        return [
            'secret' => $plainSecret,

            'qr_code_uri' => $this->totpService
                ->generateQrCodeUri(
                    email: $user->email,
                    secret: $plainSecret,
                ),

            'provider' => $dto->provider
                ->value,
        ];
    }

    public function confirm(
        ConfirmTwoFactorDTO $dto,
    ): array {

        $user = $this->repository
            ->findUserById(
                $dto->userId,
            );

        if (! $user) {
            throw new RuntimeException(
                'User not found.',
            );
        }

        if (! $user->two_factor_secret) {
            throw new RuntimeException(
                'Two-factor secret not found.',
            );
        }

        $secret = $this->secretService
            ->decrypt(
                $user->two_factor_secret,
            );

        if (! $this->totpService->verify(
            secret: $secret,
            code: $dto->code,
        )) {
            throw new InvalidTwoFactorCodeException;
        }

        $this->repository
            ->updateTwoFactor(
                $user,
                new UpdateTwoFactorData(
                    enabled: true,
                    provider: TwoFactorProvider::from(
                        $user->two_factor_provider,
                    ),
                    secret: $user->two_factor_secret,
                    confirmedAt: now(),
                    lastVerifiedAt: now(),
                ),
            );

        $recoveryCodes = $this->recoveryCodeService
            ->generate(
                $user,
            );

        event(
            new TwoFactorConfirmed(
                $user,
            ),
        );

        event(
            new TwoFactorEnabled(
                $user,
            ),
        );

        event(
            new RecoveryCodesGenerated(
                $user,
                codes: $recoveryCodes,
            ),
        );

        return [
            'recovery_codes' => $recoveryCodes,
        ];
    }

    public function verify(
        VerifyTwoFactorDTO $dto,
    ): AuthResult {

        $user = $this->repository
            ->findUserByEmail(
                $dto->identifier,
            );

        if (! $user) {
            throw new RuntimeException(
                'User not found.',
            );
        }

        if (! $user->two_factor_enabled) {
            throw new TwoFactorNotEnabledException;
        }

        $secret = $this->secretService
            ->decrypt(
                $user->two_factor_secret,
            );

        if (! $this->totpService->verify(
            secret: $secret,
            code: $dto->code,
        )) {
            throw new InvalidTwoFactorCodeException;
        }

        $this->repository
            ->updateLastVerified(
                $user,
            );

        event(
            new TwoFactorVerified(
                $user,
            ),
        );

        return $this->loginCompletionService
            ->complete(
                user: $user,
                panel: $dto->panel,
                provider: $dto->provider,
                ipAddress: $dto->ipAddress,
                userAgent: $dto->userAgent,
                deviceName: $dto->deviceName,
            );
    }

    public function disable(
        DisableTwoFactorDTO $dto,
    ): void {

        $user = $this->repository
            ->findUserById(
                $dto->userId,
            );

        if (! $user) {
            throw new RuntimeException(
                'User not found.',
            );
        }

        if (! $user->two_factor_enabled) {
            throw new TwoFactorNotEnabledException;
        }

        if (! Hash::check(
            $dto->password,
            $user->password,
        )) {
            throw new RuntimeException(
                'Current password is incorrect.',
            );
        }

        $this->repository
            ->deleteRecoveryCodes(
                $user,
            );

        $this->repository
            ->updateTwoFactor(
                $user,
                new UpdateTwoFactorData(
                    enabled: false,
                    provider: null,
                    secret: null,
                    confirmedAt: null,
                    lastVerifiedAt: null,
                ),
            );

        event(
            new TwoFactorDisabled(
                $user,
            ),
        );
    }
}
