<?php

declare(strict_types=1);

namespace App\Listeners\Notification;

use App\Domain\Auth\Events\LoginOtpSent;
use App\Domain\Notifications\DTO\SendNotificationDTO;
use App\Domain\Notifications\Enums\NotificationChannel;
use App\Domain\Notifications\NotificationTypes;
use App\Domain\Notifications\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyLoginOtpSent implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(LoginOtpSent $event): void
    {
        if ($event->userId === null) {
            return;
        }

        $this->notificationService->send(new SendNotificationDTO(
            type: NotificationTypes::LOGIN_OTP_SENT,
            recipientId: (int) $event->userId,
            payload: [
                'identifier' => $event->identifier,
                'otp_code' => $event->otpCode,
                'expires_at' => $event->expiresAt->toIso8601String(),
                'purpose' => $event->purpose,
            ],
            channels: [NotificationChannel::DATABASE, NotificationChannel::BROADCAST, NotificationChannel::MAIL],
            // Strong idempotency: OTP purpose + expiry timestamp
            idempotencyKey: sprintf('otp:%d:%s:%d', $event->userId, $event->purpose, $event->expiresAt->getTimestamp()),
        ));
    }
}