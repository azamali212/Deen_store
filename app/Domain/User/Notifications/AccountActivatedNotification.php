<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AccountActivatedNotification extends Notification implements ShouldQueue
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
            ->subject('Your Account Has Been Reactivated')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Good news — your account has been reactivated.')
            ->line('You can now sign in normally.')
            ->salutation('Regards,'.PHP_EOL.config('app.name').' Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'account_activated',
            'title' => 'Account Reactivated',
            'message' => 'Your account has been reactivated.',
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
