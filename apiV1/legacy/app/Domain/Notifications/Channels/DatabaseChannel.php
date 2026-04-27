<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Channels;

use App\Domain\Notifications\Enums\DeliveryStatus;
use App\Models\Notification;
use App\Models\NotificationDelivery;

final class DatabaseChannel implements NotificationChannelInterface
{
    public function send(Notification $notification, NotificationDelivery $delivery, array $payload): DeliveryStatus
    {
        // DB delivery = Notification row exists; delivery row will be marked SENT.
        $delivery->payload = $payload;

        return DeliveryStatus::SENT;
    }
}