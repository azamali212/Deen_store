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
use Illuminate\Support\Str;
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
use Illuminate\Http\JsonResponse;

final class CustomerAuthController extends Controller
{
    public function login(LoginRequest $request, LoginUserAction $action): AuthResultResource
    {
        $dto = LoginDTO::fromArray($request->validated(), AuthPanel::CUSTOMER);

        return new AuthResultResource($action->execute($dto));
    }


    public function verifyOtp(
        VerifyOtpRequest $request,
        VerifyOtpAction $action,
    ): AuthResultResource {
        //dd($request->all());
        $dto = VerifyOtpDTO::fromArray(
            $request->validated(),
            AuthPanel::CUSTOMER,
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
        $dto = LogoutDTO::fromArray(
            $request->validated(),
            (string) $request->user()->id
        );

        $action->execute($request->user(), $dto);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    
}
