<?php

namespace App\Listeners;

use App\Events\OrderActionEvent;
use App\Notifications\OrderActionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class OrderActionListener implements ShouldQueue
{
    public function handle(OrderActionEvent $event)
    {
        // Send Pusher notification
        $message = $this->getNotificationMessage($event->action, $event->order);

        // Notify the customer or admin based on the action
        Notification::send($event->order->customer, new OrderActionNotification($event->order, $message));
    }

    private function getNotificationMessage(string $action, $order): string
    {
        switch ($action) {
            case 'created':
                return "Your order #{$order->id} has been created!";
            case 'cancelled':
                return "Your order #{$order->id} has been cancelled.";
            case 'escalated':
                return "Order #{$order->id} has been escalated due to delay.";
            case 'refunded':
                return "Order #{$order->id} has been refunded.";
            case 'predicted':
                return "Order prediction report is available.";
            case 'processed':
                return "Your order #{$order->id} is now being processed.";
            case 'status':
                return "Your order #{$order->id} Status Has Changed.";
            default:
                return "An action has been performed on your order #{$order->id}.";
        }
    }
}
