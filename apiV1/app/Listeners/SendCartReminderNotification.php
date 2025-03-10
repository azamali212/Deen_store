<?php

namespace App\Listeners;

use App\Events\CartAbandonedEvent;
use App\Notifications\CartReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCartReminderNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    use InteractsWithQueue;

    public function handle(CartAbandonedEvent $event)
    {
        $event->cart->user->notify(new CartReminderNotification($event->cart));
    }
}
