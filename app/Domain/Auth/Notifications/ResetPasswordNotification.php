<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token,
    ) {}

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

        $resetUrl = config('app.frontend_url')
            . '/reset-password?token='
            . $this->token;

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We received a request to reset your password.')
            ->action(
                'Reset Password',
                $resetUrl
            )
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.')
            ->salutation(
                'Regards,' . PHP_EOL . config('app.name') . ' Team'
            );
    }

    public function toArray(
        object $notifiable
    ): array {

        return [

            'type' => 'password_reset',

            'title' => 'Reset your password',

            'message' => 'A password reset was requested for your account.',

            'token' => $this->token,

            'created_at' => now()->toDateTimeString(),

        ];
    }
}
