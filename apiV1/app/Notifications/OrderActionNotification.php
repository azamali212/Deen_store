<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class OrderActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;
    public $message;

    public function __construct(Order $order, string $message)
    {
        $this->order = $order;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['broadcast','pusher']; // We are using broadcast channel for real-time notifications
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'order_id' => $this->order->id,
            'message' => $this->message,
            'order_status' => $this->order->order_status,
            'action' => 'Order Action',
        ]);
    }
}
