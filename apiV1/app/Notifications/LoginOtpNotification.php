<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your login')
            ->greeting('Security Verification')
            ->line('We detected a suspicious login attempt.')
            ->line("Your OTP code is: **{$this->otp}**")
            ->line('This code expires in 10 minutes.')
            ->line('If this wasn’t you, please reset your password immediately.');
    }
}