<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BaseMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $data;
    public $template;
    public $subject;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $template, $data = [])
    {
        $this->user = $user;
        $this->template = $template;
        $this->data = $data;
        $this->subject = $template->subject ?? 'Notification'; // Default subject if not set
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->subject)
                    ->view('emails.dynamic_template')
                    ->with([
                        'user' => $this->user,
                        'data' => $this->data,
                        'content' => $this->template->content,
                    ]);
    }
}