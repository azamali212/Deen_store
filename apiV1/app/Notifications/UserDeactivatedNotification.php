<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class UserDeactivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $deactivatedBy;
    protected $reason;


    /**
     * Create a new notification instance.
     */
    public function __construct($user, $deactivatedBy, $reason = '')
    {
        $this->user = $user;
        $this->deactivatedBy = $deactivatedBy;
        $this->reason = $reason;
    }
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail', 'broadcast', 'pusher'];
    }
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => 'Your account has been deactivated',
            'user_id' => $notifiable->id,
            'deactivated_by' => $this->deactivatedBy->name,
            'reason' => $this->reason,
            'action_url' => config('app.frontend_url') . '/support'
        ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Account Deactivated')
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('Your account has been deactivated by ' . $this->deactivatedBy->name . '.')
            ->line('Reason: ' . ($this->reason ?: 'No reason provided'))
            ->line('If you believe this was done in error, please contact support.')
            ->action('Contact Support', config('app.frontend_url') . '/support')
            ->line('Thank you for using our application!')
            ->salutation('Regards, ' . config('app.name'));
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'message' => 'Your account has been deactivated',
            'user_id' => $notifiable->id,
            'deactivated_by' => $this->deactivatedBy->name,
            'reason' => $this->reason,
            'action_url' => config('app.frontend_url') . '/support'
        ];
    }
}
