<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Actions\LoginUserAction;
use App\Domain\Auth\Actions\LogoutUserAction;
use App\Domain\Auth\Actions\RegisterAdminAction;
use App\Domain\Auth\Actions\VerifyOtpAction;
use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\LogoutDTO;
use App\Domain\Auth\DTO\RegisterAdminDTO;
use App\Domain\Auth\DTO\VerifyOtpDTO;
use App\Domain\Auth\Enums\AuthPanel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\RegisterAdminRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\Auth\AuthResultResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminAuthController extends Controller
{
    public function login(LoginRequest $request, LoginUserAction $action): AuthResultResource
    {
        $dto = LoginDTO::fromArray($request->validated(), AuthPanel::ADMIN);
        return new AuthResultResource($action->execute($dto));
    }

    public function register(RegisterAdminRequest $request, RegisterAdminAction $action): JsonResponse
    {
        $dto = RegisterAdminDTO::fromArray($request->validated(), (string) $request->user()->id);
        $user = $action->execute($dto);

        return response()->json([
            'success' => true,
            'message' => 'Admin created successfully.',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request, VerifyOtpAction $action): JsonResponse
    {
        $dto = VerifyOtpDTO::fromArray($request->validated());
        $user = User::query()->where('email',$dto->identifier)->firstOrFail();

        return response()->json([
            'success' => $action->execute($user, $dto->code, $dto->purpose),

        ]);
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
}
