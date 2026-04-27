<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OtpService
{
    public function generateOtp($userId, $sessionId): string
    {
        $otp = (string) random_int(100000, 999999);

        DB::table('login_otps')->insert([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10),
            'created_at' => now(),
        ]);

        return $otp;
    }

    public function verifyOtp($userId, $sessionId, $otp): bool
    {
        $record = DB::table('login_otps')
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record || $record->otp !== $otp) {
            return false;
        }

        DB::table('login_otps')->where('id', $record->id)->delete();

        return true;
    }
}