<?php

namespace App\Listeners;

use App\Events\EmailStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleEmailStatusUpdated implements ShouldQueue
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
    public function handle(EmailStatusUpdated $event): void
    {
        //dump($event->email);
        \Log::info('Email status updated for email: ' . $event->email->id);
    }
}
