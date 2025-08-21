<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $token;
    protected $user;

    public function __construct($token, $user = null)
    {
        $this->token = $token;
        $this->user = $user;
    }

    public function via($notifiable)
    {
        // Enable all channels like CartReminderNotification
        return ['mail', 'broadcast', 'pusher'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => 'Email verification sent!',
            'user_id' => $notifiable->id,
            'action_url' => $this->verificationUrl()
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Email verification sent!',
            'user_id' => $notifiable->id,
            'action_url' => $this->verificationUrl()
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->greeting('Hello ' . ($this->user->name ?? 'User') . '!')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $this->verificationUrl())
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Regards, ' . config('app.name'));
    }

    protected function verificationUrl()
    {
        return config('app.frontend_url') . '/verify-email/' . $this->token;
    }
}