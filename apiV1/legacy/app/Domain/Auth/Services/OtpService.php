<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Events\LoginOtpSent;
use App\Models\LoginOtp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class OtpService
{
    public function sendOtp(
        string $identifier,
        string $purpose,
        ?int $userId = null,
        int $ttlMinutes = 10,
        int $maxAttempts = 5,
    ): LoginOtp {
        $code = (string) random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes($ttlMinutes);

        $otp = DB::transaction(function () use ($identifier, $purpose, $userId, $code, $expiresAt, $maxAttempts): LoginOtp {
            LoginOtp::query()
                ->where('identifier', $identifier)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => Carbon::now()]);

            return LoginOtp::query()->create([
                'user_id' => $userId,
                'identifier' => $identifier,
                'purpose' => $purpose,
                'code_hash' => Hash::make($code),
                'expires_at' => $expiresAt,
                'attempts' => 0,
                'max_attempts' => $maxAttempts,
            ]);
        });

        event(new LoginOtpSent(
            userId: $userId,
            identifier: $identifier,
            otpCode: $code,
            expiresAt: $expiresAt,
            purpose: $purpose,
        ));

        return $otp;
    }

    public function verifyOtp(string $identifier, string $purpose, string $code): bool
    {
        return DB::transaction(function () use ($identifier, $purpose, $code): bool {
            $otp = LoginOtp::query()
                ->where('identifier', $identifier)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', Carbon::now())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($otp === null) {
                return false;
            }

            if ((int) $otp->attempts >= (int) $otp->max_attempts) {
                throw new RuntimeException('Maximum OTP attempts reached.');
            }

            $otp->increment('attempts');

            if (!Hash::check($code, (string) $otp->code_hash)) {
                return false;
            }

            $otp->consumed_at = Carbon::now();
            $otp->save();

            return true;
        });
    }
}