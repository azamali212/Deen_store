<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AccountUnlockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $reason,
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
            ->subject('Your Account Has Been Unlocked')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your account has been unlocked successfully.')
            ->line('Reason: '.$this->reason)
            ->line('You can now sign in to your account again.')
            ->line('If you were not expecting this change, please contact support immediately.')
            ->salutation('Regards,'.PHP_EOL.config('app.name').' Security Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'account_unlocked',
            'title' => 'Account Unlocked',
            'message' => 'Your account has been unlocked successfully.',
            'reason' => $this->reason,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}