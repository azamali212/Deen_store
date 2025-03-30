<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $inventory;

    public function __construct($message, $inventory)
    {
        $this->message = $message;
        $this->inventory = $inventory;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('inventory-updates');
    }

    public function broadcastAs()
    {
        return 'inventory.updated';
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'inventory' => $this->inventory
        ];
    }
}
