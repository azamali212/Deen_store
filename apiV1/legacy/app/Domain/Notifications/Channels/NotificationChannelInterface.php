<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Channels;

use App\Domain\Notifications\Enums\DeliveryStatus;
use App\Models\Notification;
use App\Models\NotificationDelivery;

interface NotificationChannelInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function send(Notification $notification, NotificationDelivery $delivery, array $payload): DeliveryStatus;
}