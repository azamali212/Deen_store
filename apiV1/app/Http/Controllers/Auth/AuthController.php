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
use App\Services\Auth\OtpService;
use App\Services\Auth\SessionService;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\Auth;

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
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);
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
            'email'    => 'required|email',
            'password' => 'required',
            'portal'   => 'required|string|in:admin,customer'
        ]);

        try {
            // IMPORTANT: Controller never handles guard/token/session
            // All logic must run inside repository
            $response = $this->authRepository->login(
                $request->only('email', 'password'),
                $request->portal,   // pass portal to repository
                [
                    'ip'      => $request->ip(),
                    'browser' => $request->userAgent(),
                ]
            );

            return response()->json($response, 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'session_id' => 'required|string',
            'otp' => 'required|string',
            'portal' => 'required|string|in:admin,customer'
        ]);
    
        try {
            return response()->json(
                $this->authRepository->verifyOtp(
                    $data['email'],
                    $data['session_id'],
                    $data['otp']
                )
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
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

    /***********************************************
     * GET USER PROFILE
     ***********************************************/
    public function getProfile()
    {
        try {
            return response()->json(
                $this->authRepository->getCurrentUser()
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
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
