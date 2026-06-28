<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewDeviceLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $deviceName,
        private readonly string $browser,
        private readonly string $operatingSystem,
        private readonly string $ipAddress,
    ) {}

    public function via(object $notifiable): array
    {
        return [
            'mail',
            'database',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Device Login')
            ->greeting('Hello!')
            ->line('Your account was accessed from a new device.')
            ->line('Device: '.$this->deviceName)
            ->line('Browser: '.$this->browser)
            ->line('Operating System: '.$this->operatingSystem)
            ->line('IP Address: '.$this->ipAddress)
            ->line('If this was not you, please secure your account immediately.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'device_name' => $this->deviceName,
            'browser' => $this->browser,
            'operating_system' => $this->operatingSystem,
            'ip_address' => $this->ipAddress,
        ];
    }
}