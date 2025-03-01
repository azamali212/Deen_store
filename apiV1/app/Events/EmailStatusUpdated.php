<?php 
namespace App\Events;

use App\Models\Email;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $email;

    public function __construct($emailId)
{
    // Ensure that we only fetch the email if it exists
    $this->email = Email::find($emailId);

    if (!$this->email) {
        \Log::error('Email not found for ID: ' . $emailId);
        return;
    }

    \Log::info('EmailStatusUpdated event fired for email ID: ' . $this->email->id);
}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('emails.' . $this->email->receiver_id), // Listen to channel specific to receiver_id
        ];
    }

    public function broadcastAs()
    {
        return 'email.status.updated';
    }

    public function broadcastWith()
    {
        return [
            'email_id' => $this->email->id,
            'status' => $this->email->status, // Add any other data you want to broadcast
        ];
    }
}