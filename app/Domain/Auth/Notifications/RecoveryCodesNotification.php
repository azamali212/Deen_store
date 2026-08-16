<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RecoveryCodesNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly array $codes,
    ) {}

    public function via(object $notifiable): array
    {
        return [
            'mail',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your Recovery Codes')
            ->greeting("Hello {$notifiable->name},")
            ->line('Store these recovery codes in a safe place.');

        foreach ($this->codes as $code) {
            $mail->line($code);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Recovery Codes Generated',
        ];
    }
}