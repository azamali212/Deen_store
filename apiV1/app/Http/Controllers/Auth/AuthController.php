<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterValidationRequest;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Notifications\WelcomeEmailNotification;
use App\Repositories\Auth\AuthRepository;
use App\Repositories\Auth\AuthRepositoryInterface;
use App\Repositories\Email\EmailRepositoryInterface;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    protected $authRepository;
    protected $emailRepository;

    public function __construct(AuthRepositoryInterface $authRepository, EmailRepositoryInterface $emailRepository)
    {
        $this->authRepository = $authRepository;
        $this->emailRepository = $emailRepository;
    }

    public function register(RegisterValidationRequest $request)
    {
        try {
            // Register user
            $user = $this->authRepository->register($request->validated());

            // Send verification email
            //$user->sendEmailVerificationNotification();

            return response()->json(['message' => 'Registration successful. Please check your email for verification.', 'user' => $user], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'guard' => 'sometimes|string' // Optional guard parameter
        ]);

        return $this->authRepository->login(
            $request->only('email', 'password'),
            $request->input('guard')
        );
    }

    public function verifyEmail(Request $request)
    {
        $token = $request->route('token');

        if (!$token) {
            return response()->json(['error' => 'Missing verification token.'], 400);
        }

        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid token or user not found.'], 400);
        }

        // Mark the email as verified
        $user->email_verified_at = now();
        $user->email_verification_token = null;  // Clear the token
        $user->save();

        // Send the welcome email using the notification
        try {
            //$template = EmailTemplate::getTemplateByName('welcome_email_template'); // Or use any logic to fetch the correct template
            //$user->notify(new WelcomeEmailNotification($user, $template)); // Trigger the notification

            return response()->json(['message' => 'Email verified successfully. Welcome email sent.', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send welcome email: ' . $e->getMessage()], 400);
        }
    }

    public function resendVerificationEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            return $this->authRepository->resendVerificationEmail($request->email);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function switchRole(Request $request, AuthRepository $authRepository)
    {
        $request->validate(['role' => 'required|string']);

        try {
            $user = $authRepository->switchRole($request->user(), $request->role);
            return response()->json(['message' => 'Role switched successfully', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    public function forgotPassword(Request $request, AuthRepository $authRepository)
    {
        $request->validate(['email' => 'required|email']);

        return $authRepository->forgotPassword($request->email);
    }

    public function resetPassword(Request $request, AuthRepository $authRepository)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        return $authRepository->resetPassword($request->token, $request->password);
    }


    public function logout(Request $request)
    {
        try {
            $this->authRepository->logout();
            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
