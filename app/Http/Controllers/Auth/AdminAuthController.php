<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Actions\CreateUserAction;
use App\Domain\Auth\Actions\LoginUserAction;
use App\Domain\Auth\Actions\LogoutUserAction;
use App\Domain\Auth\Actions\VerifyOtpAction;
use App\Domain\Auth\DTO\CreateUserDTO;
use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\LogoutDTO;
use App\Domain\Auth\DTO\VerifyOtpDTO;
use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Services\DeviceFingerprintService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CreateUserRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\Auth\AuthResultResource;
use App\Http\Resources\Auth\UserResource;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use App\Domain\Auth\Actions\VerifyEmailAction;
use App\Domain\Auth\DTO\VerifyEmailDTO;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\Auth\VerifyEmailResource;
use App\Domain\Auth\Actions\ResendVerificationAction;
use App\Domain\Auth\DTO\ResendVerificationDTO;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Resources\Auth\ResendVerificationResource;
use App\Domain\Auth\Actions\ForgotPasswordAction;
use App\Domain\Auth\Actions\ResetPasswordAction;
use App\Domain\Auth\DTO\ForgotPasswordDTO;
use App\Domain\Auth\DTO\ResetPasswordDTO;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Auth\ForgotPasswordResource;
use App\Http\Resources\Auth\ResetPasswordResource;
use App\Domain\Auth\Actions\ChangePasswordAction;
use App\Domain\Auth\DTO\ChangePasswordDTO;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Resources\Auth\ChangePasswordResource;
use App\Domain\Auth\Actions\ListSessionsAction;
use App\Domain\Auth\Actions\LogoutSessionAction;
use App\Domain\Auth\Actions\LogoutOtherSessionsAction;
use App\Domain\Auth\DTO\LogoutSessionDTO;
use App\Domain\Auth\DTO\LogoutOtherSessionsDTO;
use App\Http\Requests\Auth\LogoutSessionRequest;
use App\Http\Resources\Auth\ActiveSessionCollection;
use Illuminate\Support\Facades\Request;

final class AdminAuthController extends Controller
{
    public function login(LoginRequest $request, LoginUserAction $action): AuthResultResource
    {
        $dto = LoginDTO::fromArray($request->validated(), AuthPanel::ADMIN, $request->ip(), $request->userAgent());
        return new AuthResultResource($action->execute($dto));
    }

    public function register(
        CreateUserRequest $request,
        CreateUserAction $action
    ): JsonResponse {
        $dto = CreateUserDTO::fromArray(
            $request->validated(),
            (string) $request->user()->id
        );
        $user = $action->execute($dto);
        return response()->json([
            'success' => true,
            'message' => 'User account created successfully.',
            'data' => [
                'user' => new UserResource($user),
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

    public function verifyOtp(
        VerifyOtpRequest $request,
        VerifyOtpAction $action,
    ): AuthResultResource {

        $dto = VerifyOtpDTO::fromArray(
            $request->validated(),
            AuthPanel::ADMIN,
            $request->ip(),
            $request->userAgent(),
            app(DeviceFingerprintService::class)
                ->deviceName($request),
        );

        return new AuthResultResource(
            $action->execute($dto)
        );
    }

    public function logout(LogoutRequest $request, LogoutUserAction $action): JsonResponse
    {
        $dto = LogoutDTO::fromArray($request->validated(), (string) $request->user()->id);
        $action->execute($request->user(), $dto);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function verifyEmail(
        VerifyEmailRequest $request,
        VerifyEmailAction $action,
    ): VerifyEmailResource {

        $dto = VerifyEmailDTO::fromArray(
            $request->validated()
        );

        return new VerifyEmailResource(
            $action->execute($dto)
        );
    }

    public function resendVerification(ResendVerificationRequest $request, ResendVerificationAction $action,): ResendVerificationResource
    {
        $dto = ResendVerificationDTO::fromArray(
            $request->validated()
        );
        $action->execute(
            $dto
        );
        return new ResendVerificationResource(
            null
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action,): ForgotPasswordResource
    {
        $dto = ForgotPasswordDTO::fromArray(
            $request->validated(),
            $request->ip(),
            $request->userAgent(),
        );

        $action->execute(
            $dto
        );

        return new ForgotPasswordResource(
            null
        );
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action,): ResetPasswordResource
    {
        $dto = ResetPasswordDTO::fromArray(
            $request->validated(),
            $request->ip(),
            $request->userAgent(),
        );

        $action->execute(
            $dto
        );

        return new ResetPasswordResource(
            null
        );
    }

    public function changePassword(ChangePasswordRequest $request, ChangePasswordAction $action,): ChangePasswordResource
    {
        $dto = ChangePasswordDTO::fromArray(
            $request->validated(),
            (string) $request->user()->id,
        );
        $action->execute(
            $dto
        );
        return new ChangePasswordResource(
            null
        );
    }

    public function sessions(\Illuminate\Http\Request $request, ListSessionsAction $action,): ActiveSessionCollection
    {
        return new ActiveSessionCollection(
            $action->execute(
                (string) $request->user()->id
            )
        );
    }

    public function logoutSession(LogoutSessionRequest $request, LogoutSessionAction $action,): JsonResponse
    {
        $dto = LogoutSessionDTO::fromArray(
            $request->validated(),
            (string) $request->user()->id,
        );
        $action->execute(
            $dto
        );
        return response()->json([
            'success' => true,
            'message' => 'Session terminated successfully.',
        ]);
    }

    public function logoutOtherSessions(LogoutOtherSessionsAction $action,\Illuminate\Http\Request $request): JsonResponse
    {
        $dto = LogoutOtherSessionsDTO::fromUser(
            (string) $request->user()->id,
            (string) $request->user()->currentAccessToken()->id,
        );
        $action->execute(
            $dto
        );
        return response()->json([
            'success' => true,
            'message' => 'Other sessions terminated successfully.',
        ]);
    }
}
