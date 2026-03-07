<?php

namespace App\Repositories\Auth;

use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use App\Services\Auth\SessionService;
use App\Services\Auth\SuspiciousLoginService;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use App\Services\Auth\AuditService;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Notifications\DTO\SendNotificationDTO;
use App\Domain\Notifications\Enums\NotificationChannel;
use App\Domain\Notifications\NotificationTypes;

// IMPORTANT: use DOMAIN otp service (central notifications)
use App\Domain\Auth\Services\OtpService as DomainOtpService;

class AuthRepository implements AuthRepositoryInterface
{
    protected UserRepositoryInterface $userRepository;
    protected SessionService $sessionService;
    protected TokenService $tokenService;
    protected SuspiciousLoginService $suspiciousService;
    protected DomainOtpService $otpService;
    protected AuditService $auditService;

    protected NotificationService $notificationService;

    public function __construct(
        UserRepositoryInterface $repo,
        SessionService $sessionService,
        TokenService $tokenService,
        SuspiciousLoginService $suspiciousService,
        DomainOtpService $otpService,
        AuditService $auditService,
        NotificationService $notificationService
    ) {
        $this->userRepository   = $repo;
        $this->sessionService  = $sessionService;
        $this->tokenService    = $tokenService;
        $this->suspiciousService = $suspiciousService;
        $this->otpService      = $otpService;
        $this->auditService    = $auditService;
        $this->notificationService = $notificationService;
    }

    public function register(array $data)
    {
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        $user->email_verification_token = Str::random(60);
        $user->save();

        $roleName = $data['role'] ?? 'Customer';
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            throw new Exception("Role '{$roleName}' does not exist.");
        }
        $user->assignRole($role);

        return $user;
    }

    public function getCurrentUser()
    {
        $user = Auth::user();
        if (!$user) {
            throw new Exception("User not authenticated");
        }

        $user->load('roles');

        return [
            'success' => true,
            'message' => 'User profile retrieved successfully',
            'user' => $this->formatUser($user)
        ];
    }

    public function login(array $credentials, ?string $portal = null, ?array $location = null)
    {
        $email = $credentials['email'];
        $this->throttleLogin($email);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $this->auditService->log(
                event: 'login_failed',
                email: $email,
                meta: ['reason' => 'invalid_credentials']
            );
            $this->sessionService->recordFailedAttempt($email);
            throw new Exception("Invalid credentials");
        }

        if ($user->status !== 'active') {
            $this->auditService->log(
                event: 'login_failed',
                userId: $user->id,
                email: $user->email,
                meta: ['reason' => 'inactive_account']
            );
            throw new Exception("Your account is inactive.");
        }

        $this->validatePortalAccess($user, $portal);

        $guard = $this->sessionService->resolveGuardFromPortal($portal, $user);

        $session = $this->sessionService->createSession($user, $guard, $portal, $location);

        $isSuspicious = $this->suspiciousService->detect($user, $session);

        if ($isSuspicious) {
            // Purpose bound to session => strongest security
            $purpose = 'login:' . $session->session_id;

            // CENTRAL OTP: creates login_otps + fires event + creates notification + broadcasts
            $this->otpService->sendOtp(
                identifier: (string) $user->email,
                purpose: $purpose,
                userId: (int) $user->id,
                ttlMinutes: 10,
                maxAttempts: 5
            );

            $this->auditService->log(
                event: 'otp_sent',
                userId: $user->id,
                email: $user->email,
                sessionId: $session->session_id,
                meta: ['reason' => 'suspicious_login']
            );

            return [
                'success' => false,
                'requires_verification' => true,
                'session_id' => $session->session_id,
                'message' => 'Suspicious login detected. OTP sent.'
            ];
        }

        $tokens = $this->tokenService->generateTokens($user, $session->session_id);

       

        $this->auditService->log(
            event: 'login_success',
            userId: $user->id,
            email: $user->email,
            sessionId: $session->session_id,
            meta: [
                'portal' => $portal,
                'guard' => $guard
            ]
        );

        return [
            'success' => true,
            'message' => 'Login successful',
            'token' => $tokens,
            'user' => $this->formatUser($user),
            'session' => [
                'session_id' => $session->session_id,
                'device' => $session->device,
                'ip' => $session->ip,
                'portal' => $portal,
            ],
            'access' => $this->sessionService->getAccessPages($user),
        ];
    }

    public function verifyOtp(string $email, string $sessionId, string $otp)
    {
        $user = User::where('email', $email)->firstOrFail();

        $sessionRow = $this->sessionService->getSessionById($sessionId);
        if (!$sessionRow || (string)$sessionRow->user_id !== (string)$user->id) {
            throw new Exception('Invalid session.');
        }

        $purpose = 'login:' . $sessionId;

        // CENTRAL verify (reads login_otps table)
        $isValid = $this->otpService->verifyOtp(
            identifier: (string) $user->email,
            purpose: $purpose,
            code: (string) $otp
        );

        if (!$isValid) {
            $this->auditService->log(
                event: 'otp_failed',
                userId: $user->id,
                email: $user->email,
                sessionId: $sessionId
            );

            throw new Exception('Invalid or expired OTP');
        }

        $tokens = $this->tokenService->generateTokens($user, $sessionId);

        $this->notificationService->send(
            new SendNotificationDTO(
                type: NotificationTypes::LOGIN_ALERT,
                recipientId: (int) $user->id,
                payload: [
                    'identifier' => $user->email,
                    'user_name'  => $user->name,
                    'ip'         => (string) ($sessionRow->ip ?? ''),
                    'device'     => (string) ($sessionRow->device ?? ''),
                    'portal'     => (string) ($sessionRow->login_portal ?? ''),
                    'login_time' => now()->toDateTimeString(),
                ],
                channels: [NotificationChannel::MAIL],
                idempotencyKey: 'login-alert:' . $sessionId
            )
        );

        $this->auditService->log(
            event: 'otp_verified',
            userId: $user->id,
            email: $user->email,
            sessionId: $sessionId
        );

        return [
            'success' => true,
            'message' => 'OTP verified successfully',
            'token' => $tokens,
            'user' => $this->formatUser($user),
            'session' => [
                'session_id' => $sessionId,
                'device' => $sessionRow->device,
                'ip' => $sessionRow->ip,
                'portal' => $sessionRow->login_portal,
            ],
            'access' => $this->sessionService->getAccessPages($user),
        ];
    }

    private function throttleLogin(string $email): void
    {
        $key = "login:throttle:" . $email;

        RateLimiter::hit($key, 300);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw new Exception("Too many login attempts. Try again in 5 minutes.");
        }
    }

    private function validatePortalAccess(User $user, ?string $portal)
    {
        if (!$portal) return;

        if ($portal === 'admin' && !$user->hasAnyRole(['Super Admin', 'Admin'])) {
            throw new Exception("Unauthorized for admin portal.");
        }

        if ($portal === 'customer' && !$user->hasRole('Customer')) {
            throw new Exception("You do not have access to the customer portal.");
        }
    }

    public function refreshToken(string $refreshToken)
    {
        return $this->tokenService->refresh($refreshToken);
    }

    public function logout()
    {
        $user = Auth::user();

        if ($user) {
            $this->auditService->log(
                event: 'logout',
                userId: $user->id,
                email: $user->email
            );
        }

        return $this->sessionService->logoutCurrentDevice();
    }

    public function logoutFromDevice(string $sessionId)
    {
        return $this->sessionService->logoutSpecificDevice($sessionId);
    }

    public function switchRole(User $user, string $role)
    {
        return $this->sessionService->switchUserRole($role);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'status' => $user->status,
            'roles' => $user->getRoleNames(),
            'active_role' => $user->getRoleNames()->first(),
        ];
    }

    public function verifyEmail($token)
    {
        $user = User::where('email_verification_token', $token)
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            throw new Exception("Invalid or expired token.");
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();

        return $user;
    }

    public function resendVerificationEmail($email)
    {
        $user = User::where('email', $email)->whereNull('email_verified_at')->first();
        if (!$user) throw new Exception("User not found or already verified.");

        $key = "verify:{$user->id}";

        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw new Exception("Wait before requesting another verification email.");
        }

        RateLimiter::hit($key, 3600 * 24);

        $user->email_verification_token = Str::random(60);
        $user->save();

        $user->sendEmailVerificationNotification();

        return ['message' => 'Verification email resent'];
    }

    public function forgotPassword($email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) throw new Exception("User not found.");

        $token = Str::random(64);

        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        //$user->notify(new \App\Notifications\ForgotPasswordNotification($token));

        return ['message' => 'Password reset email sent'];
    }

    public function resetPassword($token, $newPassword)
    {
        $rows = \DB::table('password_reset_tokens')->get();

        foreach ($rows as $row) {
            if (Hash::check($token, $row->token)) {
                $user = User::where('email', $row->email)->first();
                if (!$user) throw new Exception("User not found.");

                $user->password = Hash::make($newPassword);
                $user->save();

                \DB::table('password_reset_tokens')->where('email', $row->email)->delete();

                //$user->notify(new \App\Notifications\ResetPasswordNotification($user));

                return ['message' => 'Password reset successful'];
            }
        }

        throw new Exception("Invalid token.");
    }

    public function getCurrentGuard($tokenName)
    {
        return Cache::get("auth:guard:{$tokenName}");
    }

    public function getDefaultGuardForRole($role)
    {
        return match ($role) {
            'Customer' => 'customer-api',
            'Vendor',
            'Store Admin',
            'Order Admin',
            'Product Admin',
            'Admin',
            'Super Admin' => 'admin-api',
            default => 'customer-api'
        };
    }

    public function getValidGuardsForRole($role)
    {
        return match ($role) {
            'Customer' => ['customer-api'],
            'Vendor',
            'Store Admin',
            'Product Admin',
            'Order Admin',
            'Admin',
            'Super Admin' => ['admin-api'],
            default => ['customer-api']
        };
    }
}
