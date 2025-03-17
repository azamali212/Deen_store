<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderActionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $action;

    public function __construct(Order $order, string $action)
    {
        $this->order = $order;
        $this->action = $action;
    }

    public function broadcastOn()
    {
        return new Channel('order-actions');
    }

    public function broadcastAs()
    {
        return 'order-action';
    }
}
