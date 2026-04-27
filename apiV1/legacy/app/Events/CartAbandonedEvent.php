<?php

namespace App\Events;

use App\Models\Cart;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartAbandonedEvent implements ShouldBroadcast
{
    use SerializesModels, InteractsWithSockets;

    public $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('cart.'.$this->cart->user_id);
    }

    public function broadcastAs()
    {
        return 'cart.abandoned';
    }
}
