<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notification;

use App\Domain\Notifications\Enums\NotificationChannel;
use App\Domain\Notifications\Repositories\PreferenceRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\UpdateNotificationPreferenceRequest;
use Illuminate\Http\JsonResponse;

final class NotificationPreferenceController extends Controller
{
    public function update(
        UpdateNotificationPreferenceRequest $request,
        PreferenceRepository $preferenceRepository,
    ): JsonResponse {
        $validated = $request->validated();
        $userId = isset($validated['user_id']) ? (int) $validated['user_id'] : (int) $request->user()?->id;

        if ($userId <= 0) {
            return response()->json([
                'message' => 'A valid user context is required.',
            ], 422);
        }

        $preference = $preferenceRepository->upsertPreference(
            userId: $userId,
            notificationType: $validated['type'] ?? '*',
            channel: NotificationChannel::from($validated['channel']),
            enabled: (bool) $validated['enabled'],
        );

        return response()->json([
            'id' => $preference->id,
            'user_id' => $preference->user_id,
            'type' => $preference->notification_type,
            'channel' => $preference->channel,
            'enabled' => $preference->enabled,
        ]);
    }
}