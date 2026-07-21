<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AccountLockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $reason,
        private readonly ?string $lockedUntil,
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
            ->subject('Security Alert: Account Locked')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your account has been locked because suspicious login activity was detected.')
            ->line('Reason: '.$this->reason)
            ->line('Locked until: '.($this->lockedUntil ?? 'Manual administrator unlock required.'))
            ->line('If this activity was not performed by you, please reset your password and contact support.')
            ->salutation('Regards,'.PHP_EOL.config('app.name').' Security Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'account_locked',
            'title' => 'Account Locked',
            'message' => 'Your account has been locked due to suspicious login activity.',
            'reason' => $this->reason,
            'locked_until' => $this->lockedUntil,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}