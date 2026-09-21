<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProfileCompletedNotification extends Notification implements ShouldQueue
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
            ->subject('Your Profile is 100% Complete!')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Congratulations — your profile is now 100% complete.')
            ->line('A complete profile helps you get the most out of '.config('app.name').'.')
            ->salutation('Regards,'.PHP_EOL.config('app.name').' Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'profile_completed',
            'title' => 'Profile Complete',
            'message' => 'Your profile is now 100% complete.',
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
