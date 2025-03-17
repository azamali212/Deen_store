<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class EscalateDelayedOrders extends Command
{
    protected $signature = 'orders:escalate-delayed';
    protected $description = 'Escalate delayed orders that have not been processed for 5 days';

    public function handle()
    {
        // Fetch orders that are pending and have been created more than 5 days ago
        $orders = Order::where('order_status', 'pending')
            ->where('created_at', '<', now()->subDays(5))
            ->get();

        foreach ($orders as $order) {
            if ($order->isDelayed()) {
                $order->markAsDelayed(); // Mark the order as delayed if not already marked
                $order->escalateOrder(); // Escalate the order
                $this->info("Escalated Order ID {$order->id}");
            }
        }
    }
}
