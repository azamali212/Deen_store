<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Repositories;

use App\Domain\Notifications\Enums\NotificationChannel;
use App\Models\NotificationPreference;

final class PreferenceRepository
{
    /**
     * @return array<int, NotificationChannel>
     */
    public function resolveChannelsForUser(int $userId, string $notificationType): array
    {
        $records = NotificationPreference::query()
            ->where('user_id', $userId)
            ->whereIn('notification_type', ['*', $notificationType])
            ->get();

        $defaults = [
            NotificationChannel::DATABASE->value => true,
            NotificationChannel::BROADCAST->value => true,
            NotificationChannel::MAIL->value => false,
            NotificationChannel::SMS->value => false,
        ];

        foreach ($records as $record) {
            $defaults[$record->channel] = (bool) $record->enabled;
        }

        $channels = [];
        foreach ($defaults as $channel => $enabled) {
            if ($enabled) {
                $channels[] = NotificationChannel::from($channel);
            }
        }

        return $channels;
    }

    public function upsertPreference(
        int $userId,
        string $notificationType,
        NotificationChannel $channel,
        bool $enabled,
    ): NotificationPreference {
        return NotificationPreference::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'notification_type' => $notificationType,
                'channel' => $channel->value,
            ],
            ['enabled' => $enabled],
        );
    }
}