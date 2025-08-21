<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $activatedBy;
    protected $reason;

    public function __construct($user, $activatedBy, $reason = '')
    {
        $this->user = $user;
        $this->activatedBy = $activatedBy;
        $this->reason = $reason;
    }

    public function via($notifiable)
    {
        return ['mail', 'broadcast', 'database'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => 'Your account has been activated',
            'user_id' => $notifiable->id,
            'activated_by' => $this->activatedBy->name,
            'reason' => $this->reason,
            'action_url' => config('app.frontend_url') . '/login'
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Your account has been activated',
            'user_id' => $notifiable->id,
            'activated_by' => $this->activatedBy->name,
            'reason' => $this->reason,
            'action_url' => config('app.frontend_url') . '/login'
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Account Activated')
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('Your account has been activated by ' . $this->activatedBy->name . '.')
            ->line('Reason: ' . ($this->reason ?: 'No reason provided'))
            ->action('Login to your account', config('app.frontend_url') . '/login')
            ->line('Thank you for using our application!')
            ->salutation('Regards, ' . config('app.name'));
    }
}