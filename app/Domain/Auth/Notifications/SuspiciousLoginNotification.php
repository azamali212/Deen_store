<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use App\Domain\Auth\Events\Data\SuspiciousLoginEventData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SuspiciousLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SuspiciousLoginEventData $data,
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
            ->subject('Suspicious Login Detected')
            ->greeting('Security Alert')
            ->line('We detected a suspicious login attempt.')
            ->line('IP Address: '.$this->data->ipAddress)
            ->line('Device: '.$this->data->deviceName)
            ->line('If this was not you, please change your password immediately.');
    }

    public function toArray(object $notifiable): array
    {
        return $this->data->toArray();
    }
}