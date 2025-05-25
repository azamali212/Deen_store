<?php

namespace App\Repositories\Auth;

use App\Models\User;
use App\Notifications\ForgotPasswordNotification;
use App\Notifications\ResetPasswordNotification;
use App\Repositories\Email\EmailRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Jobs\LogUserActionJob;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Cache\RateLimiter;
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

    public function login(array $credentials)
    {
        $email = $credentials['email'] ?? 'unknown@example.com'; // fallback
        $key = 'login_attempts:' . strtolower($email);
        $maxAttempts = 3;
        $decayMinutes = 5;

        if (FacadeRateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = FacadeRateLimiter::availableIn($key);
            return response()->json([
                'message' => "Too many login attempts. Please try again in {$seconds} seconds."
            ], 429);
        }

        if (!Auth::attempt($credentials)) {
            FacadeRateLimiter::hit($key, $decayMinutes * 60);

            $remainingAttempts = FacadeRateLimiter::remaining($key, $maxAttempts);

            return response()->json([
                'message' => 'Invalid credentials. ' . ($remainingAttempts > 0
                    ? "You have {$remainingAttempts} more attempt(s)."
                    : "Account locked. Try again in {$decayMinutes} minutes.")
            ], 401);
        }

        // Login successful
        FacadeRateLimiter::clear($key); // reset attempts on success

        $user = Auth::user();

        if ($user instanceof User) {
            $user->update(['last_login_at' => now()]);
            $user->refresh();
            $token = $user->createToken('device_name')->plainTextToken;

            LogUserActionJob::dispatch($user->id, 'user_registered', [
                'event_type' => 'registration',
                'status' => 'success',
                'details' => 'User successfully registered and assigned role: ' . $user->getRoleNames(),
                'reference' => $user,
            ]);

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,
            ]);
        }

        return response()->json(['message' => 'Error logging in'], 500);
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
