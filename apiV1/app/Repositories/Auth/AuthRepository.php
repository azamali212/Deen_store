<?php

namespace App\Repositories\Auth;

use App\Models\User;
use App\Notifications\ForgotPasswordNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\RateLimiter;
use App\Jobs\LogUserActionJob;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter as FacadeRateLimiter;

class AuthRepository implements AuthRepositoryInterface
{
    protected $userRepository;
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function register(array $data)
    {
        $request = request();
        // Hash password
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        // Generate verification token
        $user->email_verification_token = Str::random(60); // Generate token
        $user->save();

        // Check if a role is provided, otherwise default to 'Customer'
        $role = isset($data['role']) ? $data['role'] : 'Customer';

        // Assign the role using 'api' guard
        $role = Role::findByName($role, 'api');
        $user->assignRole($role);

        $deviceDetial = $this->userRepository->getDeviceType($request);
        $browserDetial = $this->userRepository->getBrowser($request);
        LogUserActionJob::dispatch($user->id, 'user_registered', [
            'event_type'    => 'registration',
            'status'        => 'success',
            'details'       => 'User successfully registered and assigned role: ' . $role->name,
            'reference'     => $user,
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
            'device_type'   => $deviceDetial,
            'browser'       => $browserDetial,
            'platform'      => php_uname('s'),
            'route_name'    => $request->route()?->getName(),
            'url'           => $request->fullUrl(),
        ]);

        return $user;
    }

    public function login(array $credentials, $guard = null)
    {
        $email = $credentials['email'] ?? 'unknown@example.com';
        $key = 'login_attempts:' . strtolower($email);
        $maxAttempts = 3;
        $decayMinutes = 5;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Too many login attempts. Please try again in {$seconds} seconds."
            ], 429);
        }

        // Find user by email
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, $decayMinutes * 60);
            $remainingAttempts = RateLimiter::remaining($key, $maxAttempts);

            return response()->json([
                'message' => 'Invalid credentials. ' .
                    ($remainingAttempts > 0 ? "You have {$remainingAttempts} more attempt(s)." :
                        "Account locked. Try again in {$decayMinutes} minutes.")
            ], 401);
        }

        // Determine guard based on role if not specified
        $guard = $guard ?: $this->getDefaultGuardForRole($user->getRoleNames()->first());

        // Login successful
        RateLimiter::clear($key);
        $user->update(['last_login_at' => now()]);

        // Get all permissions (direct + via roles) using Spatie
        $permissions = $user->getAllPermissions()->pluck('name');
        $role = $user->getRoleNames()->first();

        // Generate unique token identifier for this tab session
        $tabSessionId = Str::random(40);
        $tokenName = $guard . '_token_' . $tabSessionId;

        // Create new token without deleting existing ones (for multi-tab support)
        $token = $user->createToken($tokenName, $permissions->toArray())->plainTextToken;

        // Store the guard context for this token
        Cache::put("auth:guard:{$tokenName}", $guard, now()->addDay());

        LogUserActionJob::dispatch($user->id, 'user_login', [
            'event_type' => 'authentication',
            'status' => 'success',
            'details' => "User logged in with guard: {$guard}",
            'reference' => $user,
            'tab_session_id' => $tabSessionId,
        ]);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'guard' => $guard,
            'role' => $role,
            'permissions' => $permissions,
            'tab_session_id' => $tabSessionId, // Return to client for tab identification
        ]);
    }
    public function getCurrentGuard($tokenName)
    {
        return Cache::get("auth:guard:{$tokenName}");
    }


    public function getValidGuardsForRole($role)
    {
        // Cache the role-guard mapping for better performance
        return Cache::remember("role-guard-mapping:{$role}", now()->addDay(), function () use ($role) {
            $roleGuardMap = [
                'Super Admin' => ['super-admin-api'],
                'Admin' => ['admin-api'],
                'Vendor Admin' => ['vendor-admin-api'],
                'Customer' => ['customer-api'],
                'Delivery Manager' => ['delivery-manager-api'],
                'Store Admin' => ['store-admin-api'],
                'Product Admin' => ['product-admin-api'],
                'Order Admin' => ['order-admin-api'],
            ];

            return $roleGuardMap[$role] ?? ['api'];
        });
    }

    public function getDefaultGuardForRole($role)
    {
        // Cache the default guard mapping for better performance
        return Cache::remember("default-guard:{$role}", now()->addDay(), function () use ($role) {
            $mapping = [
                'Super Admin' => 'super-admin-api',
                'Admin' => 'admin-api',
                'Vendor Admin' => 'vendor-admin-api',
                'Customer' => 'customer-api',
                'Delivery Manager' => 'delivery-manager-api',
                'Store Admin' => 'store-admin-api',
                'Product Admin' => 'product-admin-api',
                'Order Admin' => 'order-admin-api',
            ];

            return $mapping[$role] ?? 'api';
        });
    }

    public function switchRole(User $user, string $role)
    {
        if (!$user->hasRole($role)) {
            throw new \Exception("User doesn't have this role");
        }

        $newGuard = $this->getDefaultGuardForRole($role);

        // Delete existing tokens
        $user->tokens()->delete();

        // Get all permissions for the new role
        $permissions = $user->getAllPermissions()->pluck('name');

        // Create new token with all permissions
        $tokenName = $newGuard . '_token_' . now()->timestamp;
        $token = $user->createToken($tokenName, $permissions->toArray())->plainTextToken;

        $user->update(['current_guard' => $newGuard]);

        return response()->json([
            'message' => 'Role switched successfully',
            'token' => $token,
            'guard' => $newGuard,
            'role' => $role,
            'permissions' => $permissions
        ]);
    }

    public function verifyEmail($token)
    {
        $user = User::where('email_verified_at', null)
            ->where('email_verification_token', $token)
            ->first();

        if ($user) {
            $user->email_verified_at = now();
            $user->email_verification_token = null;
            $user->save();
            return $user;
        }

        throw new \Exception("Invalid or expired token");
    }

    public function forgotPassword($email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception("User not found");
        }

        $token = Str::random(60);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // Send password reset notification
        $user->notify(new ForgotPasswordNotification($token));

        return response()->json(['message' => 'Password reset email sent successfully.']);
    }

    protected function sendPasswordResetEmail($user, $token)
    {
        // Send the email using the EmailRepository
        //app(EmailRepositoryInterface::class)->sendEmail($user, 'password_reset_email', ['token' => $token]);
    }

    public function resetPassword($token, $newPassword)
    {
        $resets = DB::table('password_reset_tokens')->get();

        foreach ($resets as $reset) {
            if (Hash::check($token, $reset->token)) {
                $user = User::where('email', $reset->email)->first();

                if ($user) {
                    $user->password = Hash::make($newPassword);
                    $user->save();

                    $user->notify(new ResetPasswordNotification($user));

                    DB::table('password_reset_tokens')->where('email', $reset->email)->delete();

                    return response()->json(['message' => 'Password reset successfully. A confirmation email has been sent.']);
                }

                throw new \Exception("User not found");
            }
        }

        throw new \Exception("Invalid or expired token");
    }

    public function logout()
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('User not authenticated');
        }

        $user->tokens->each(function ($token) {
            $token->delete();
        });

        return true;
    }
}
