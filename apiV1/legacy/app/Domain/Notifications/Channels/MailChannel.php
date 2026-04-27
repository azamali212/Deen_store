<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Channels;

use App\Domain\Notifications\Enums\DeliveryStatus;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Mail;

final class MailChannel implements NotificationChannelInterface
{
    public function send(Notification $notification, NotificationDelivery $delivery, array $payload): DeliveryStatus
    {
        $to = $payload['identifier'] ?? null; // your OTP uses identifier=email
        if (!$to) {
            throw new \RuntimeException('Missing email identifier in payload.');
        }

        $subject = $payload['subject'] ?? ('Notification: ' . $notification->type);
        $body    = $payload['body'] ?? json_encode($payload);

        Mail::raw((string)$body, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });

        $delivery->payload = $payload;

        return DeliveryStatus::SENT;
    }
}