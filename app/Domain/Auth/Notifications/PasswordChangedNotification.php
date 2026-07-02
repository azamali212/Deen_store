<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(
        object $notifiable
    ): array {

        return [
            'mail',
            'database',
        ];
    }

    public function toMail(
        object $notifiable
    ): MailMessage {

        return (new MailMessage)
            ->subject('Password Changed Successfully')
            ->greeting(
                'Hello '.$notifiable->name.','
            )
            ->line(
                'Your account password has been changed successfully.'
            )
            ->line(
                'If you made this change, no further action is required.'
            )
            ->line(
                'If you did not change your password, please reset your password immediately or contact support.'
            )
            ->salutation(
                'Regards,'.PHP_EOL.config('app.name').' Team'
            );
    }

    public function toArray(
        object $notifiable
    ): array {

        return [

            'type' => 'password_changed',

            'title' => 'Password Changed',

            'message' => 'Your account password has been changed successfully.',

            'created_at' => now()->toDateTimeString(),

        ];
    }
}