<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ForgotPasswordNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $token; // Store the token

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token; // Assign the token
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $resetUrl = url("/reset-password?token=" . urlencode($this->token) . "&email=" . urlencode($notifiable->email));

        return (new MailMessage)
            ->subject('Password Reset Request')
            ->line('You have requested to reset your password.')
            ->action('Reset Password', $resetUrl)
            ->line('If you did not request this, please ignore this email.')
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
