<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Domain\User\Actions\GetPreferencesAction;
use App\Domain\User\Actions\UpdatePreferencesAction;
use App\Domain\User\DTO\UpdatePreferenceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdatePreferencesRequest;
use App\Http\Resources\User\UserPreferenceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PreferenceController extends Controller
{
    // Get the authenticated user's own preferences
    public function show(
        Request $request,
        GetPreferencesAction $action,
    ): JsonResponse {

        $preferences = $action->execute(
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => $preferences !== null
                ? 'Preferences retrieved successfully.'
                : 'Preferences not set yet — defaults apply.',
            'data' => $preferences !== null
                ? new UserPreferenceResource($preferences)
                : null,
        ]);
    }

    // Create or update the authenticated user's preferences
    public function update(
        UpdatePreferencesRequest $request,
        UpdatePreferencesAction $action,
    ): UserPreferenceResource {

        $dto = UpdatePreferenceDTO::fromArray(
            $request->validated(),
        );

        return new UserPreferenceResource(
            $action->execute(
                $request->user()->id,
                $dto,
            ),
        );
    }
}
