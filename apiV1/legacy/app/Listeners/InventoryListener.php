<?php

namespace App\Listeners;

use App\Events\InventoryEvent;
use App\Notifications\InventoryNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class InventoryListener implements ShouldQueue
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
    public function handle(InventoryEvent $event)
    {
        // Notify relevant users
        Notification::route('mail', 'admin@example.com')
            ->notify(new InventoryNotification($event->message, $event->inventory));
    }
}
