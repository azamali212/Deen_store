<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InventoryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $message;
    public $inventory;

    public function __construct($message, $inventory)
    {
        $this->message = $message;
        $this->inventory = $inventory;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast','pusher'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => 'You have items left in your cart!',
            'inventory_id' => $this->inventory->id
        ]);
    }
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line($this->message)
            ->line('Product: ' . $this->inventory['product_name'])
            ->action('View Inventory', url('/inventory'))
            ->line('Thank you for using our system!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => $this->message,
            'inventory' => $this->inventory
        ];
    }
}
