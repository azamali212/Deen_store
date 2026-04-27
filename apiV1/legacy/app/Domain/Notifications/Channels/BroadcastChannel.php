<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Channels;

use App\Domain\Notifications\Enums\DeliveryStatus;
use App\Events\NotificationBroadcasted;
use App\Models\Notification;
use App\Models\NotificationDelivery;

final class BroadcastChannel implements NotificationChannelInterface
{
    public function send(Notification $notification, NotificationDelivery $delivery, array $payload): DeliveryStatus
    {
        event(new NotificationBroadcasted(
            notificationId: (int) $notification->id,
            userId: (int) $notification->recipient_id,
            type: (string) $notification->type,
            channel: (string) $delivery->channel,
            payload: $payload,
        ));

        $delivery->payload = $payload;

        return DeliveryStatus::SENT;
    }
}