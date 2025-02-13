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

class AuthRepository implements AuthRepositoryInterface
{
    public function register(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        // Generate verification token
        $user->email_verification_token = Str::random(60); // Generate token
        $user->save();

        // Assign role if provided
        if (isset($data['role'])) {
            $role = Role::findByName($data['role'], 'api');
            $user->assignRole($role);
        }

        return $user;
    }

    public function login(array $credentials)
    {
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    
        $user = Auth::user();
    
        // Explicitly type-hint $user as User
        if ($user instanceof User) {
            // Assign the token to the $token variable
            $token = $user->createToken('device_name')->plainTextToken;
    
            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,  // Now you have $token available
            ]);
        } else {
            // Handle error if $user is not an instance of User
            return response()->json(['message' => 'Error logging in'], 500);
        }
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
        app(EmailRepositoryInterface::class)->sendEmail($user, 'password_reset_email', ['token' => $token]);
    }

    public function resetPassword($token, $newPassword)
    {
        $reset = DB::table('password_reset_tokens')->where('token', $token)->first();
    
        if (!$reset) {
            throw new \Exception("Invalid or expired token");
        }
    
        $user = User::where('email', $reset->email)->first();
    
        if ($user) {
            // Update the password
            $user->password = Hash::make($newPassword);
            $user->save();
    
            // Send the password reset success notification
            $user->notify(new ResetPasswordNotification($user));  // Notify the user
            
            // Delete the reset token from the table
            DB::table('password_reset_tokens')->where('email', $reset->email)->delete();
    
            return response()->json(['message' => 'Password reset successfully. A confirmation email has been sent.']);
        }
    
        throw new \Exception("User not found");
    }

    public function logout()
    {
        Auth::user()->tokens->each(function ($token) {
            $token->delete();
        });

        return true;
    }
}
