<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeEmailNotification extends Notification
{
    use Queueable;

    protected $user;
    protected $template;
    protected $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, EmailTemplate $template, array $data = [])
    {
        $this->user = $user;
        $this->template = $template;
        $this->data = $data;
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
        // Create a mail message
        $mailMessage = (new MailMessage)
                        ->subject($this->template->subject ?? 'Welcome to Our Platform');
        
        // If content is HTML, we use `line` to append content or pass HTML directly
        $mailMessage->line($this->template->content); // If content is plain text or HTML
        
        // Optional: Add additional lines or custom content if needed
        $mailMessage->line('Thank you for joining us!');

        return $mailMessage;
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'content' => $this->template->content,
        ];
    }
}
