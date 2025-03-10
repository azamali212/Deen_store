<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CartReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $cart;

    public function __construct($cart)
    {
        $this->cart = $cart;
    }

    public function via($notifiable)
    {
        return ['mail','broadcast', 'pusher'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => 'You have items left in your cart!',
            'cart_id' => $this->cart->id
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'You have items left in your cart!',
            'cart_id' => $this->cart->id
        ];
    }
    public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('Reminder: Items in Your Cart')
        ->greeting('Hello!')
        ->line('You have items left in your cart. Complete your purchase now!')
        ->action('Go to Cart', url('/cart'))
        ->line('Thank you for shopping with us!');
}
}
