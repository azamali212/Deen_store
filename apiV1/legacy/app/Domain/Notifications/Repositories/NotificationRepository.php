<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Repositories;

use App\Domain\Notifications\DTO\SendNotificationDTO;
use App\Domain\Notifications\Enums\DeliveryStatus;
use App\Domain\Notifications\Enums\NotificationChannel;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Support\Carbon;

final class NotificationRepository
{
    public function findById(int $id): ?Notification
    {
        return Notification::query()->find($id);
    }

    public function findByIdempotency(SendNotificationDTO $dto): ?Notification
    {
        if ($dto->idempotencyKey === null || $dto->idempotencyKey === '') {
            return null;
        }

        return Notification::query()
            ->where('recipient_id', $dto->recipientId)
            ->where('recipient_type', $dto->recipientType)
            ->where('type', $dto->type)
            ->where('idempotency_key', $dto->idempotencyKey)
            ->first();
    }

    /**
     * @param array<int, NotificationChannel> $channels
     */
    public function create(SendNotificationDTO $dto, array $channels): Notification
    {
        return Notification::query()->create([
            'recipient_id' => $dto->recipientId,
            'recipient_type' => $dto->recipientType,
            'type' => $dto->type,
            'payload' => $dto->payload,
            'channels' => array_map(static fn (NotificationChannel $c): string => $c->value, $channels),
            'locale' => $dto->locale,
            'status' => DeliveryStatus::PENDING->value,
            'actor_id' => $dto->actorId,
            'idempotency_key' => $dto->idempotencyKey,
        ]);
    }

    public function firstOrCreateDelivery(Notification $notification, NotificationChannel $channel): NotificationDelivery
    {
        return NotificationDelivery::query()->firstOrCreate(
            [
                'notification_id' => $notification->id,
                'channel' => $channel->value,
            ],
            [
                'status' => DeliveryStatus::PENDING->value,
                'attempts' => 0,
            ],
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function markDelivery(
        NotificationDelivery $delivery,
        DeliveryStatus $status,
        int $attempts,
        array $metadata = [],
        ?string $errorMessage = null,
    ): void {
        $delivery->status = $status->value;
        $delivery->attempts = $attempts;
        $delivery->error_message = $errorMessage;
        $delivery->payload = $metadata;

        if ($status === DeliveryStatus::SENT) {
            $delivery->delivered_at = Carbon::now();
        }

        $delivery->save();
    }

    public function refreshAggregateStatus(Notification $notification): void
    {
        $statuses = $notification->deliveries()->pluck('status')->unique()->values();

        if ($statuses->contains(DeliveryStatus::FAILED->value)) {
            $notification->status = DeliveryStatus::FAILED->value;
        } elseif ($statuses->count() > 0 && $statuses->every(fn (string $s): bool => $s === DeliveryStatus::SENT->value)) {
            $notification->status = DeliveryStatus::SENT->value;
            $notification->sent_at = Carbon::now();
        } else {
            $notification->status = DeliveryStatus::PENDING->value;
        }

        $notification->save();
    }
}