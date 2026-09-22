<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\Auth\Enums\OtpPurpose;
use App\Domain\Auth\Services\OtpService;
use App\Domain\User\ValueObjects\PhoneNumber;
use App\Models\User;

/**
 * Thin orchestration over Auth domain's generic OtpService — reuses its
 * existing rate limiting, hashing, expiry and storage (the login_otps
 * table) instead of building a second parallel OTP mechanism. The only
 * thing specific to phone verification is *which* identifier the code is
 * tied to (the phone being verified, not the user's email) and how it's
 * delivered (logged for now — see PhoneVerificationService decision).
 */
final readonly class PhoneVerificationService
{
    public function __construct(
        private OtpService $otpService,
    ) {}

    public function sendCode(User $user, PhoneNumber $phone): void
    {
        $this->otpService->create(
            $user,
            OtpPurpose::PHONE_VERIFICATION,
            $phone->value(),
        );
    }

    public function confirm(User $user, PhoneNumber $phone, string $code): bool
    {
        $verified = $this->otpService->verify(
            $user,
            $code,
            OtpPurpose::PHONE_VERIFICATION,
        );

        if (! $verified) {
            return false;
        }

        $user->forceFill([
            'phone' => $phone->value(),
            'phone_verified_at' => now(),
        ])->save();

        return true;
    }
}
