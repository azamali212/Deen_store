<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notification;

use App\Domain\Notifications\DTO\SendNotificationDTO;
use App\Domain\Notifications\Enums\NotificationChannel;
use App\Domain\Notifications\Services\NotificationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\SendNotificationRequest;
use Illuminate\Http\JsonResponse;

final class NotificationController extends Controller
{
    public function send(SendNotificationRequest $request, NotificationService $notificationService): JsonResponse
    {
        $validated = $request->validated();

        $channels = array_map(
            static fn (string $channel): NotificationChannel => NotificationChannel::from($channel),
            $validated['channels'] ?? [],
        );

        $notification = $notificationService->send(new SendNotificationDTO(
            type: $validated['type'],
            recipientId: (int) $validated['recipient_id'],
            recipientType: $validated['recipient_type'] ?? 'user',
            payload: $validated['payload'] ?? [],
            channels: $channels,
            locale: $validated['locale'] ?? 'en',
            actorId: isset($validated['actor_id']) ? (int) $validated['actor_id'] : null,
            idempotencyKey: $validated['idempotency_key'] ?? null,
        ));

        return response()->json([
            'id' => $notification->id,
            'status' => $notification->status,
            'message' => 'Notification queued for delivery.',
        ], 202);
    }
}