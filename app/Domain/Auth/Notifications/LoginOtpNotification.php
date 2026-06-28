<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class LoginOtpNotification extends Notification 
{
    use Queueable;

    private const OTP_EXPIRY_MINUTES = 10;

    public function __construct(
        private readonly string $otp,
        private readonly string $purpose,
        private readonly string $identifier,
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
            ->subject(config('app.name').' Security Verification')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A verification code has been generated for your account.')
            ->line('Purpose: '.ucwords(str_replace('_', ' ', $this->purpose)))
            ->line('')
            ->line('One Time Password')
            ->line('━━━━━━━━━━━━━━━━━━━━━━')
            ->line('**'.$this->otp.'**')
            ->line('━━━━━━━━━━━━━━━━━━━━━━')
            ->line('')
            ->line('This code will expire in '.self::OTP_EXPIRY_MINUTES.' minutes.')
            ->line('Never share this OTP with anyone.')
            ->line('If you did not initiate this request, please change your password immediately.')
            ->salutation('Regards,'.PHP_EOL.config('app.name').' Security Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'login_otp',
            'purpose' => $this->purpose,
            'identifier' => $this->identifier,
            'expires_in_minutes' => self::OTP_EXPIRY_MINUTES,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}