<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class WelcomeUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $createdBy,
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
        $role = $notifiable->getRoleNames()->first() ?? 'User';

        return (new MailMessage)
            ->subject('Welcome to '.config('app.name'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Welcome to '.config('app.name').'.')
            ->line('Your account has been created successfully.')
            ->line('Email: '.$notifiable->email)
            ->line('Role: '.ucwords(str_replace('_', ' ', $role)))
            ->line('You can now sign in using your registered email address and password.')
            ->line('If you did not expect this account to be created, please contact our support team immediately.')
            ->salutation('Regards,'.PHP_EOL.config('app.name').' Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'title' => 'Welcome to '.config('app.name'),
            'message' => 'Your account has been created successfully.',
            'created_by' => $this->createdBy,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}