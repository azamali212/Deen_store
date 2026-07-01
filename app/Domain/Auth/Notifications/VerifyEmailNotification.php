<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class VerifyEmailNotification extends Notification implements ShouldQueue
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

        $verificationUrl = config('app.frontend_url')
            . '/verify-email?token='
            . $this->token;

        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Thank you for creating your account.')
            ->line('Please verify your email address to activate your account.')
            ->action(
                'Verify Email',
                $verificationUrl
            )
            ->line('This verification link will expire in 60 minutes.')
            ->line('If you did not create this account, no further action is required.')
            ->salutation(
                'Regards,'.PHP_EOL.config('app.name').' Team'
            );
    }

    public function toArray(
        object $notifiable
    ): array {

        return [
            'type' => 'email_verification',
            'title' => 'Verify your email',
            'message' => 'Please verify your email address.',
            'token' => $this->token,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}