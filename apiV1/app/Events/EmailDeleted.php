<?php

namespace App\Events;

use App\Models\Email;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $email;

    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('emails.' . $this->email->receiver_id),
        ];
    }

    public function broadcastWith()
    {
        return [
            'message' => 'An email has been permanently deleted.',
            'email_id' => $this->email->id,
            'deleted_at' => now()->toDateTimeString(),
        ];
    }
}
