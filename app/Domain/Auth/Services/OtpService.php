<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Enums\OtpPurpose;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreateOtpData;
use App\Models\LoginOtp;
use App\Models\User;
use Carbon\Carbon;

final readonly class OtpService
{
    private const OTP_LENGTH = 6;

    private const OTP_EXPIRY_MINUTES = 10;

    private const MAX_ACTIVE_OTPS = 3;

    public function __construct(
        private AuthRepositoryInterface $repository,
    ) {}

    public function generateOtp(): string
    {
        return str_pad(
            (string) random_int(0, 999999),
            self::OTP_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }

    public function create(
        User $user,
        OtpPurpose $purpose
    ): LoginOtp {

        $this->invalidateActiveOtps(
            $user,
            $purpose
        );

        return $this->repository->createOtp(
            new CreateOtpData(

                identifier: $user->email,

                codeHash: bcrypt($this->generateOtp()),

                purpose: $purpose,

                expiresAt: now()->addMinutes(self::OTP_EXPIRY_MINUTES),

                userId: (string) $user->id,

            )
        );
    }

    public function verify(
        User $user,
        string $code,
        OtpPurpose $purpose
    ): bool {

        $otp = $this->repository
            ->findValidOtp(
                userId: $user->id,
                code: $code,
                purpose: $purpose->value,
            );

        if (! $otp) {
            return false;
        }

        $otp->update([
            'verified_at' => now(),
        ]);

        return true;
    }

    public function invalidateActiveOtps(
        User $user,
        OtpPurpose $purpose
    ): void {

        $this->repository
            ->invalidateOtps(
                $user->id,
                $purpose->value
            );
    }

    public function isExpired(
        LoginOtp $otp
    ): bool {

        return Carbon::parse(
            $otp->expires_at
        )->isPast();
    }

    public function canRequestOtp(
        User $user,
        OtpPurpose $purpose
    ): bool {

        return $this->repository
            ->countActiveOtps(
                $user->id,
                $purpose->value
            ) < self::MAX_ACTIVE_OTPS;
    }
}
