<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Enums\OtpPurpose;
use App\Domain\Auth\Events\Data\OtpEventData;
use App\Domain\Auth\Events\OtpSent;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreateOtpData;
use App\Models\LoginOtp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

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
        OtpPurpose $purpose,
        ?string $identifier = null,
    ): LoginOtp {

        if (! $this->canRequestOtp(
            $user,
            $purpose
        )) {

            throw new \RuntimeException(
                'OTP request limit exceeded.'
            );
        }

        $this->invalidateActiveOtps(
            $user,
            $purpose
        );

        $plainOtp = $this->generateOtp();

        // Falls back to email for every existing purpose (login, password
        // reset, ...) so those callers are unaffected. Only a caller that
        // explicitly passes $identifier (phone verification does) changes
        // where the OTP is tied to and how it's delivered below.
        $identifier ??= $user->email;

        $otp = $this->repository->createOtp(

            new CreateOtpData(

                identifier: $identifier,

                codeHash: Hash::make(
                    $plainOtp
                ),

                purpose: $purpose,

                expiresAt: now()->addMinutes(
                    self::OTP_EXPIRY_MINUTES
                ),

                userId: (string) $user->id,

            )

        );

        // Delivery happens in SendOtpNotificationListener (triggered by the
        // OtpSent event below) — not here — so there is exactly ONE place
        // that sends/logs an OTP, not two. (Previously this method also
        // called $user->notify() directly, which meant every OTP was
        // delivered twice: once here, once via the listener.)
        event(
            new OtpSent(
                new OtpEventData(
                    userId: (string) $user->id,
                    identifier: $identifier,
                    code: $plainOtp,
                    purpose: $purpose,
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                    occurredAt: now(),
                )
            )

        );

        return $otp;
    }

    public function verify(
        User $user,
        string $code,
        OtpPurpose $purpose
    ): bool {

        $otp = $this->repository
            ->findValidOtp(
                userId: $user->id,
                purpose: $purpose->value,
            );

        if (! $otp instanceof LoginOtp) {
            return false;
        }

        if ($this->isExpired($otp)) {
            return false;
        }

        if (! Hash::check(
            $code,
            $otp->code
        )) {
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
