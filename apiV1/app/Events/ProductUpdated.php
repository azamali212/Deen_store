<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductUpdated implements ShouldBroadcast

{
    use InteractsWithSockets, SerializesModels;

    public $product;
    public $notificationType;

    /**
     * Create a new event instance.
     *
     * @param  Product  $product
     * @param  string  $notificationType
     * @return void
     */
    public function __construct(Product $product, $notificationType)
    {
        $this->product = $product;
        $this->notificationType = $notificationType;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('product-updates'); // Broadcasting on this channel
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'notification_type' => $this->notificationType,
        ];
    }
}
