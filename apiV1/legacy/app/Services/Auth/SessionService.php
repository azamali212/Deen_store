<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\LoginLog;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Exception;
use Laravel\Sanctum\PersonalAccessToken;

class SessionService
{
    /**
     * Resolve guard from portal + role
     */
    public function resolveGuardFromPortal(?string $portal, User $user): string
    {
        if ($portal === 'admin') {
            return 'admin-api';
        }

        if ($portal === 'customer') {
            return 'customer-api';
        }

        // future portals fallback
        return 'customer-api';
    }

    /**
     * Create multi-device session
     */
    public function createSession(
        User $user,
        string $guard,
        ?string $portal,
        ?array $location = null
    ) {
        $agent = new Agent();
        $sessionId = (string) Str::ulid();

        LoginLog::create([
            'session_id'   => $sessionId,
            'user_id'      => $user->id,
            'email'        => $user->email,
            'guard'        => $guard,
            'login_portal' => $portal,

            'ip'       => request()->ip(),
            'device'   => $agent->device() ?: 'Unknown',
            'browser'  => $agent->browser() ?: request()->userAgent(),
            'os'       => $agent->platform() ?: 'Unknown',

            'country'  => $location['country'] ?? null,
            'city'     => $location['city'] ?? null,
            'timezone' => $location['timezone'] ?? null,

            'success'  => true,
        ]);

        return (object) [
            'session_id' => $sessionId,
            'guard' => $guard,
            'portal' => $portal,
            'ip' => request()->ip(),
            'device' => $agent->device(),
        ];
    }

    /**
     * Logout current device only
     */
    public function logoutCurrentDevice()
{
    $user = Auth::user();

    if (!$user) {
        throw new Exception('Not authenticated');
    }

    /** @var PersonalAccessToken|null $token */
    $token = $user->currentAccessToken();

    $token?->delete();

    return ['message' => 'Logged out from current device'];
}

    /**
     * Logout a specific session (multi-tab / multi-device)
     */
    public function logoutSpecificDevice(string $sessionId)
    {
        LoginLog::where('session_id', $sessionId)
            ->update(['success' => false]);

        return ['message' => 'Device logged out'];
    }
    // App\Services\Auth\SessionService.php
    public function getSessionById(string $sessionId): ?object
    {
        $row = \DB::table('login_logs')->where('session_id', $sessionId)->first();
        return $row ? (object) $row : null;
    }
    /**
     * Login throttle + failed attempts
     */
    public function recordFailedAttempt(string $email): void
    {
        $key = "login:fail:{$email}";

        RateLimiter::hit($key, 300);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw new Exception('Too many login attempts');
        }

        LoginLog::create([
            'session_id' => (string) Str::ulid(),
            'email' => $email,
            'ip' => request()->ip(),
            'browser' => request()->userAgent(),
            'success' => false,
        ]);
    }

    /**
     * Frontend access mapping (Next.js)
     */
    public function getAccessPages(User $user): array
    {
        if ($user->hasRole('Customer')) {
            return [
                'portal' => 'customer',
                'redirect' => '/customer/dashboard'
            ];
        }

        return [
            'portal' => 'admin',
            'redirect' => '/admin/dashboard'
        ];
    }

    /**
     * Role switching = token reset
     */
    public function switchUserRole(string $role): array
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole($role)) {
            throw new Exception('Role not allowed');
        }

        $user->tokens()->delete();

        return [
            'token' => $user->createToken("role_{$role}")->plainTextToken,
            'active_role' => $role
        ];
    }
}
