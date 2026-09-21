<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Domain\User\Actions\GetProfileAction;
use App\Domain\User\Actions\UpdateProfileAction;
use App\Domain\User\DTO\UpdateProfileDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\User\UserProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfileController extends Controller
{
    // Get the authenticated user's own profile
    public function show(
        Request $request,
        GetProfileAction $action,
    ): JsonResponse {

        $profile = $action->execute(
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => $profile !== null
                ? 'Profile retrieved successfully.'
                : 'Profile not created yet.',
            'data' => $profile !== null
                ? new UserProfileResource($profile)
                : null,
        ]);
    }

    // Create or update the authenticated user's own profile
    public function update(
        UpdateProfileRequest $request,
        UpdateProfileAction $action,
    ): UserProfileResource {

        $dto = UpdateProfileDTO::fromArray(
            $request->validated(),
        );

        return new UserProfileResource(
            $action->execute(
                $request->user()->id,
                $dto,
            ),
        );
    }
}
