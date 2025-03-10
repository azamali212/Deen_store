<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Notifications\CartReminderNotification;
use App\Events\CartAbandonedEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class CartAbandonmentReminderJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    public function handle()
    {
        // Send cart abandonment reminder if it is abandoned for more than a week
        if ($this->cart->updated_at < now()->subWeek()) {
            event(new CartAbandonedEvent($this->cart));
            Notification::send($this->cart->user, new CartReminderNotification($this->cart));

            // Log the event for auditing
            Log::info("Abandoned cart reminder sent for cart: {$this->cart->id}.");
        }

        // Delete the abandoned cart after 1 week
        if ($this->cart->updated_at < now()->subWeek()) {
            $this->cart->delete();
            Log::info("Abandoned cart removed for cart: {$this->cart->id}.");
        }
    }
}