<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TwoFactorEnabledNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            ->subject('Two-Factor Authentication Enabled')
            ->greeting("Hello {$notifiable->name},")
            ->line('Two-factor authentication has been enabled on your account.')
            ->line('If this was not you, please secure your account immediately.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Two-Factor Authentication Enabled',
            'message' => 'Two-factor authentication has been enabled successfully.',
        ];
    }
}