<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Actions\LoginUserAction;
use App\Domain\Auth\Actions\LogoutUserAction;
use App\Domain\Auth\Actions\RegisterSellerAction;
use App\Domain\Auth\Actions\VerifyOtpAction;
use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\DTO\LogoutDTO;
use App\Domain\Auth\DTO\RegisterSellerDTO;
use App\Domain\Auth\DTO\VerifyOtpDTO;
use App\Domain\Auth\Enums\AuthPanel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\RegisterSellerRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\Auth\AuthResultResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class SellerAuthController extends Controller
{
    public function login(LoginRequest $request, LoginUserAction $action): AuthResultResource
    {
        $dto = LoginDTO::fromArray($request->validated(), AuthPanel::SELLER);

        return new AuthResultResource($action->execute($dto));
    }

    public function register(RegisterSellerRequest $request, RegisterSellerAction $action): JsonResponse
    {
        $dto = RegisterSellerDTO::fromArray($request->validated());

        $user = $action->execute($dto);

        return response()->json([
            'success' => true,
            'message' => 'Seller registered successfully.',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request, VerifyOtpAction $action): JsonResponse
    {
        $dto = VerifyOtpDTO::fromArray($request->validated());

        $user = User::query()->where('email', $dto->identifier)->firstOrFail();

        return response()->json([
            'success' => $action->execute($user, $dto->code, $dto->purpose),
        ]);
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