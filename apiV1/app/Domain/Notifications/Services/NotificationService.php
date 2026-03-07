<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\DTO\SendNotificationDTO;
use App\Domain\Notifications\Enums\NotificationChannel;
use App\Domain\Notifications\Repositories\NotificationRepository;
use App\Domain\Notifications\Repositories\PreferenceRepository;
use App\Jobs\Notification\ProcessNotificationJob;
use App\Models\Notification;

final class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly PreferenceRepository $preferenceRepository,
    ) {}

    public function send(SendNotificationDTO $dto): Notification
    {
        $existing = $this->notificationRepository->findByIdempotency($dto);
        if ($existing !== null) {
            return $existing;
        }

        $channels = $this->resolveChannels($dto);
        $notification = $this->notificationRepository->create($dto, $channels);

        foreach ($channels as $channel) {
            $this->notificationRepository->firstOrCreateDelivery($notification, $channel);
        }

        ProcessNotificationJob::dispatch(
            notificationId: (int) $notification->id,
            channels: array_map(static fn (NotificationChannel $c): string => $c->value, $channels),
            locale: $dto->locale,
        )->onQueue('notifications');

        return $notification;
    }

    /**
     * @return array<int, NotificationChannel>
     */
    private function resolveChannels(SendNotificationDTO $dto): array
    {
        if ($dto->channels !== []) {
            return array_values(array_unique($dto->channels, SORT_REGULAR));
        }

        if ($dto->recipientType === 'user') {
            return $this->preferenceRepository->resolveChannelsForUser($dto->recipientId, $dto->type);
        }

        return [NotificationChannel::DATABASE, NotificationChannel::BROADCAST];
    }
}