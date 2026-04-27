<?php

namespace App\Listeners;

use App\Events\EmailDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Pusher\Pusher;

class SendEmailDeletedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(EmailDeleted $event)
    {
        $email = $event->email;
        $receiver = $email->receiver;

        // Log for debugging
        \Log::info('EmailDeleted event fired for receiver ID: ' . $receiver->id);

        // No need to send a notification manually, as the event already broadcasts it.
    }
}
