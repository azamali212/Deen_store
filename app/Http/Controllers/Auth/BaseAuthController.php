<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Actions\ChangePasswordAction;
use App\Domain\Auth\Actions\CreateUserAction;
use App\Domain\Auth\Actions\ForgotPasswordAction;
use App\Domain\Auth\Actions\ListSessionsAction;
use App\Domain\Auth\Actions\ListTrustedDevicesAction;
use App\Domain\Auth\Actions\LoginUserAction;
use App\Domain\Auth\Actions\LogoutOtherSessionsAction;
use App\Domain\Auth\Actions\LogoutSessionAction;
use App\Domain\Auth\Actions\LogoutUserAction;
use App\Domain\Auth\Actions\ResendVerificationAction;
use App\Domain\Auth\Actions\ResetPasswordAction;
use App\Domain\Auth\Actions\RevokeTrustedDeviceAction;
use App\Domain\Auth\Actions\UnlockAccountAction;
use App\Domain\Auth\Actions\VerifyEmailAction;
use App\Domain\Auth\Actions\VerifyOtpAction;
use App\Domain\Auth\DTO\ChangePasswordDTO;
use App\Domain\Auth\DTO\CreateUserDTO;
use App\Domain\Auth\DTO\ForgotPasswordDTO;
use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\LogoutDTO;
use App\Domain\Auth\DTO\LogoutOtherSessionsDTO;
use App\Domain\Auth\DTO\LogoutSessionDTO;
use App\Domain\Auth\DTO\ResendVerificationDTO;
use App\Domain\Auth\DTO\ResetPasswordDTO;
use App\Domain\Auth\DTO\RevokeTrustedDeviceDTO;
use App\Domain\Auth\DTO\UnlockAccountDTO;
use App\Domain\Auth\DTO\VerifyEmailDTO;
use App\Domain\Auth\DTO\VerifyOtpDTO;
use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Services\DeviceFingerprintService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\CreateUserRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\LogoutSessionRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\RevokeTrustedDeviceRequest;
use App\Http\Requests\Auth\UnlockAccountRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\Auth\ActiveSessionCollection;
use App\Http\Resources\Auth\AuthResultResource;
use App\Http\Resources\Auth\ChangePasswordResource;
use App\Http\Resources\Auth\ForgotPasswordResource;
use App\Http\Resources\Auth\ResendVerificationResource;
use App\Http\Resources\Auth\ResetPasswordResource;
use App\Http\Resources\Auth\RevokeTrustedDeviceResource;
use App\Http\Resources\Auth\TrustedDeviceCollection;
use App\Http\Resources\Auth\UnlockAccountResource;
use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\Auth\VerifyEmailResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class BaseAuthController extends Controller
{
    abstract protected function panel(): AuthPanel;

    abstract protected function canRegister(): bool;

    // Login user and return auth result resource
    public function login(
        LoginRequest $request,
        LoginUserAction $action,
    ): AuthResultResource {
        $dto = LoginDTO::fromArray(
            $request->validated(),
            $this->panel(),
            $request->ip(),
            $request->userAgent(),
        );

        return new AuthResultResource(
            $action->execute(
                $dto,
            ),
        );
    }

    // Verify OTP and return auth result resource
    public function verifyOtp(
        VerifyOtpRequest $request,
        VerifyOtpAction $action,
    ): AuthResultResource {
        $dto = VerifyOtpDTO::fromArray(
            $request->validated(),
            $this->panel(),
            $request->ip(),
            $request->userAgent(),
            app(DeviceFingerprintService::class)
                ->deviceName($request),
        );

        return new AuthResultResource(
            $action->execute(
                $dto,
            ),
        );
    }

    // Logout user and return success message
    public function logout(
        LogoutRequest $request,
        LogoutUserAction $action,
    ): JsonResponse {
        $dto = LogoutDTO::fromArray(
            $request->validated(),
            (string) $request->user()->id,
        );
        $action->execute(
            $request->user(),
            $dto,
        );

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    // Verify email and return verify email resource
    public function verifyEmail(
        VerifyEmailRequest $request,
        VerifyEmailAction $action,
    ): VerifyEmailResource {

        $dto = VerifyEmailDTO::fromArray(
            $request->validated(),
        );

        return new VerifyEmailResource(
            $action->execute(
                $dto,
            ),
        );
    }

    // Resend verification email and return resend verification resource
    public function resendVerification(
        ResendVerificationRequest $request,
        ResendVerificationAction $action,
    ): ResendVerificationResource {

        $dto = ResendVerificationDTO::fromArray(
            $request->validated(),
        );

        $action->execute(
            $dto,
        );

        return new ResendVerificationResource(
            null,
        );
    }

    // Forgot password and return forgot password resource
    public function forgotPassword(
        ForgotPasswordRequest $request,
        ForgotPasswordAction $action,
    ): ForgotPasswordResource {

        $dto = ForgotPasswordDTO::fromArray(
            $request->validated(),
            $request->ip(),
            $request->userAgent(),
        );

        $action->execute(
            $dto,
        );

        return new ForgotPasswordResource(
            null,
        );
    }

    // Reset password and return reset password resource
    public function resetPassword(
        ResetPasswordRequest $request,
        ResetPasswordAction $action,
    ): ResetPasswordResource {

        $dto = ResetPasswordDTO::fromArray(
            $request->validated(),
            $request->ip(),
            $request->userAgent(),
        );

        $action->execute(
            $dto,
        );

        return new ResetPasswordResource(
            null,
        );
    }

    // Change password and return change password resource
    public function changePassword(
        ChangePasswordRequest $request,
        ChangePasswordAction $action,
    ): ChangePasswordResource {

        $dto = ChangePasswordDTO::fromArray(
            $request->validated(),
            (string) $request->user()->id,
        );

        $action->execute(
            $dto,
        );

        return new ChangePasswordResource(
            null,
        );
    }

    // List all active sessions for the authenticated user and return as a collection
    public function sessions(
        Request $request,
        ListSessionsAction $action,
    ): ActiveSessionCollection {

        return new ActiveSessionCollection(
            $action->execute(
                (string) $request->user()->id,
            ),
        );
    }

    // Logout a specific session and return success message
    public function logoutSession(
        LogoutSessionRequest $request,
        LogoutSessionAction $action,
    ): JsonResponse {

        $dto = LogoutSessionDTO::fromArray(
            $request->validated(),
            (string) $request->user()->id,
        );

        $action->execute(
            $dto,
        );

        return response()->json([
            'success' => true,
            'message' => 'Session terminated successfully.',
        ]);
    }

    // Logout other sessions and return success message
    public function logoutOtherSessions(
        LogoutOtherSessionsAction $action,
        Request $request,
    ): JsonResponse {

        $dto = LogoutOtherSessionsDTO::fromUser(
            (string) $request->user()->id,
            (string) $request->user()->currentAccessToken()->id,
        );

        $action->execute(
            $dto,
        );

        return response()->json([
            'success' => true,
            'message' => 'Other sessions terminated successfully.',
        ]);
    }

    // List all trusted devices for the authenticated user and return as a collection
    public function trustedDevices(
        Request $request,
        ListTrustedDevicesAction $action,
    ): TrustedDeviceCollection {

        return new TrustedDeviceCollection(
            $action->execute(
                (string) $request->user()->id,
            ),
        );
    }

    // Revoke a trusted device and return the revoked device resource
    public function revokeTrustedDevice(
        RevokeTrustedDeviceRequest $request,
        RevokeTrustedDeviceAction $action,
    ): RevokeTrustedDeviceResource {

        $dto = RevokeTrustedDeviceDTO::fromArray(
            $request->validated(),
            (string) $request->user()->id,
        );

        $action->execute(
            $dto,
        );

        return new RevokeTrustedDeviceResource(
            null,
        );
    }

    // Unlock a user account and return the unlocked account resource
    public function unlockAccount(
        UnlockAccountRequest $request,
        UnlockAccountAction $action,
    ): UnlockAccountResource {

        $dto = UnlockAccountDTO::fromArray(
            $request->validated(),
            (string) $request->user()->id,
        );

        return new UnlockAccountResource(
            $action->execute(
                $dto,
            ),
        );
    }

    // Register a new user and return the created user resource
    public function register(
        CreateUserRequest $request,
        CreateUserAction $action,
    ): JsonResponse {

        if (! $this->canRegister()) {
            abort(403, 'Registration is not allowed.');
        }

        $dto = CreateUserDTO::fromArray(
            $request->validated(),
            (string) $request->user()->id,
        );

        $user = $action->execute(
            $dto,
        );

        return response()->json([
            'success' => true,
            'message' => 'User account created successfully.',
            'data' => [
                'user' => new UserResource(
                    $user,
                ),
            ],
            'meta' => [
                'request_id' => (string) Str::ulid(),
                'timestamp' => now()->toISOString(),
                'created_by' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ],
            ],
        ], 201);
    }
}
