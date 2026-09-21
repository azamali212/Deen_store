<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AccountSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ?string $reason,
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
            ->subject('Your Account Has Been Suspended')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your account has been suspended by an administrator.')
            ->line('Reason: '.($this->reason ?? 'No reason provided.'))
            ->line('All your active sessions have been terminated.')
            ->line('If you believe this is a mistake, please contact our support team.')
            ->salutation('Regards,'.PHP_EOL.config('app.name').' Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'account_suspended',
            'title' => 'Account Suspended',
            'message' => 'Your account has been suspended.',
            'reason' => $this->reason,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
