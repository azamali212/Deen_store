<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SuspiciousLoginService
{
    /**
     * Enterprise-grade suspicious login detection
     */
    public function detect(User $user, object $session): bool
    {
        $risk = 0;

        /**
         * 1️⃣ Admin / Super Admin → ALWAYS OTP
         */
        if ($user->hasAnyRole(['Admin', 'Super Admin'])) {
            return true;
        }

        /**
         * 2️⃣ First login ever → OTP
         */
        $previousLogins = DB::table('login_logs')
            ->where('user_id', $user->id)
            ->where('session_id', '!=', $session->session_id)
            ->count();

        if ($previousLogins === 0) {
            return true;
        }

        /**
         * 3️⃣ New device detection
         */
        $knownDevice = DB::table('login_logs')
            ->where('user_id', $user->id)
            ->where('device', $session->device)
            ->where('session_id', '!=', $session->session_id)
            ->exists();

        if (!$knownDevice) {
            $risk += 40;
        }

        /**
         * 4️⃣ New IP detection
         */
        $knownIp = DB::table('login_logs')
            ->where('user_id', $user->id)
            ->where('ip', $session->ip)
            ->where('session_id', '!=', $session->session_id)
            ->exists();

        if (!$knownIp) {
            $risk += 30;
        }

        /**
         * 5️⃣ Multiple concurrent sessions from same device
         * (token sharing / tab abuse / bot risk)
         */
        $activeSessionsSameDevice = DB::table('login_logs')
            ->where('user_id', $user->id)
            ->where('device', $session->device)
            ->where('success', true)
            ->count();

        if ($activeSessionsSameDevice >= 2) {
            $risk += 30;
        }

        /**
         * 6️⃣ Too many active sessions overall
         */
        $activeSessionsTotal = DB::table('login_logs')
            ->where('user_id', $user->id)
            ->where('success', true)
            ->count();

        if ($activeSessionsTotal >= 5) {
            $risk += 40;
        }

        /**
         * 🔐 FINAL DECISION
         */
        \Log::info('SUSPICIOUS LOGIN CHECK', [
            'user_id' => $user->id,
            'risk' => $risk,
            'device' => $session->device,
            'ip' => $session->ip,
            'portal' => $session->portal,
        ]);

        return $risk >= 50;
    }
}