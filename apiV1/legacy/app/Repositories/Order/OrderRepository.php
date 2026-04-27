<?php

namespace App\Repositories\Order;

use App\Events\OrderActionEvent;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderRepository implements OrderRepositoryInterface
{

    const ESCALATION_PERIOD = 5;
    public function getAllOrders(): LengthAwarePaginator
    {
        return Order::with(['orderItems', 'customer'])->paginate(15);
    }

    public function getOrderById(int $id): ?Order
    {
        return Order::with(['orderItems', 'customer'])->find($id);
    }

    public function updateOrder(int $orderId, array $data): bool
    {
        return Order::findOrFail($orderId)->update($data);
    }

    public function deleteOrder(int $orderId): bool
    {
        return Order::findOrFail($orderId)->delete();
    }

    public function addOrderItems(int $orderId, array $items): bool
    {
        $order = Order::findOrFail($orderId);
        return $order->orderItems()->createMany($items) ? true : false;
    }

    public function updateOrderItem(int $orderItemId, array $data): bool
    {
        return OrderItem::findOrFail($orderItemId)->update($data);
    }

    public function removeOrderItem(int $orderItemId): bool
    {
        return OrderItem::findOrFail($orderItemId)->delete();
    }

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $orderItemsData = $data['order_items'] ?? [];
            unset($data['order_items']); // Remove order items from main data
    
            // Create the main order
            $order = Order::create($data);
    
            // Fire order created event
            event(new OrderActionEvent($order, 'created'));
    
            // Create order items if present
            if (!empty($orderItemsData)) {
                $order->orderItems()->createMany($orderItemsData);
            }
    
            // Return with orderItems loaded
            return $order->load('orderItems');
        });
    }

    public function changeOrderStatus(int $orderId, string $status): bool
    {
        $order = Order::findOrFail($orderId);
        event(new OrderActionEvent($order, 'status'));
        return $order->update(['order_status' => $status]);
    }

    public function getOrdersByFilters(array $filters): Collection
    {
        $query = Order::query();

        if (isset($filters['status'])) {
            $query->where('order_status', $filters['status']);
        }
        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (isset($filters['date_range'])) {
            $query->whereBetween('created_at', $filters['date_range']);
        }

        return $query->get();
    }
    public function prioritizeOrders(): array
    {
        $vipCustomers = Order::whereHas('customer', function ($query) {
            $query->whereHas('loyaltyPoints', function ($subQuery) {
                $subQuery->where('points', '>', 1000);
            });
        })->get();
        //dd($vipCustomers);

        $highValueOrders = Order::where('grand_total', '>', 500)->get();

        return [
            'vip_customers' => $vipCustomers,
            'high_value_orders' => $highValueOrders,
        ];
    }

    public function getDelayedOrders(): Collection
    {
        return Order::whereNotNull('delayed_at')->get();
    }

    // Mark an order as delayed
    public function markOrderAsDelayed(Order $order)
    {
        if (!$order->delayed_at) {
            $order->delayed_at = Carbon::now();
            $order->save();
        }
    }

    // Escalate an order
    public function escalateOrder(Order $order)
    {
        $order->order_status = 'escalated';
        $order->escalated_at = Carbon::now();
        $order->escalation_status = 'escalated';
        $order->save();
    }

    // Handle escalation process for delayed orders
    public function escalateDelayedOrders(): bool
    {
        $orders = $this->getDelayedOrders();
        \Log::info('Checking delayed orders for escalation...');

        if ($orders->isEmpty()) {
            \Log::info('No delayed orders found for escalation.');
            return false; // Exit if no orders are found
        }

        $escalated = false;
        foreach ($orders as $order) {
            \Log::info('Checking order:', ['order' => $order->toArray()]);

            if ($order->isDelayed()) {
                \Log::info('Escalating order:', ['order_number' => $order->order_number]);
                $this->markOrderAsDelayed($order);
                $this->escalateOrder($order);
                $escalated = true;
                \Log::info('Order escalated:', ['order_number' => $order->order_number]);
            } else {
                \Log::info('Order is not delayed enough for escalation:', ['order_number' => $order->order_number]);
            }
        }

        if ($escalated) {
            \Log::info('Delayed orders have been escalated.');
        } else {
            \Log::info('No orders were escalated.');
        }

        return $escalated;
    }
    public function getHighValueOrders(): Collection
    {
        return Order::where('total_amount', '>', 1000)->get();
    }

    public function archiveOldOrders(): bool
    {
        $oldOrders = Order::where('created_at', '<', now()->subYear())->get();

        foreach ($oldOrders as $order) {
            $order->update(['is_archived' => true]);
        }

        \Log::info("📦 Archived " . count($oldOrders) . " old orders.");

        return true;
    }

    public function predictOrderTrends(): array
    {
        return Order::selectRaw('DATE(created_at) as date, COUNT(*) as total_orders')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
    }

    public function detectFraudulentOrder(int $orderId): bool
    {
        $order = Order::findOrFail($orderId);

        // Check if the order has an associated customer
        $customer = $order->customer;
        if (!$customer) {
            Log::warning("Order ID: {$orderId} does not have a valid customer.");
            return false; // Or handle this case appropriately
        }

        // Business logic for detecting fraudulent orders
        $isHighRiskCustomer = $customer->risk_level === 'high';
        $isLargeOrder = $order->total_amount > 5000;
        $isFraudulentHistory = $customer->orders()->where('is_fraudulent', true)->exists();
        $isSuspiciousAddress = $this->isSuspiciousAddress($order->shipping_address);

        // Add business logic to determine if the order is fraudulent
        if ($isLargeOrder && $isHighRiskCustomer && ($isFraudulentHistory || $isSuspiciousAddress)) {
            Log::warning("Fraudulent order detected for Order ID: {$order->id}");
            return true;
        }

        return false;
    }

    /**
     * Check if the address is suspicious based on some business logic.
     *
     * @param string $address
     * @return bool
     */
    private function isSuspiciousAddress(string $address): bool
    {
        // Example: Check if the address contains certain patterns or suspicious keywords
        $suspiciousKeywords = ['unknown', 'invalid', 'suspicious'];

        foreach ($suspiciousKeywords as $keyword) {
            if (strpos(strtolower($address), $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    public function automateOrderProcessing(int $orderId): bool
    {
        $order = Order::findOrFail($orderId);

        // Check if the order is already processed
        if ($order->order_status === 'processed') {
            Log::info("Order ID: {$orderId} is already processed.");
            return false;
        }

        // Check if the order has any payment issues or fraud flags
        if ($this->detectFraudulentOrder($orderId)) {
            Log::warning("Order ID: {$orderId} is flagged as fraudulent and cannot be processed.");
            return false;
        }

        // Process the order automatically
        $order->update(['order_status' => 'processing', 'processed_at' => now()]);

        // Dispatch an event for order processing
        event(new OrderActionEvent($order, 'processed'));

        // Send email notification after order processing
        $this->sendOrderProcessedNotification($order);

        // Log the successful processing
        Log::info("Order ID: {$orderId} has been successfully processed.");

        return true;
    }

    /**
     * Send a notification to the customer when their order is processed.
     *
     * @param Order $order
     * @return void
     */
    private function sendOrderProcessedNotification(Order $order)
    {
        // Assuming a Notification class exists
        //$order->customer->notify(new OrderProcessedNotification($order));
        event(new OrderActionEvent($order, 'processed'));
    }
}
