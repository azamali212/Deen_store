<?php

namespace App\Repositories\Order;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class OrderRepository implements OrderRepositoryInterface
{
    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return Order::paginate($perPage);
    }

    // Find order by ID with caching for performance
    public function show(int $id)
    {
        $order = Order::findOrFail($id);
        return $order;
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    // Update order details
    public function update(array $data, int $id): ?Order
    {
        $order = $this->find($id);
        if ($order) {
            $order->update($data);
            return $order; // Return the updated order object
        }
        return null; // Return null if the order is not found
    }

    // Delete an order by ID
    public function delete(int $id): bool
    {
        $order = $this->find($id);
        if ($order) {
            return $order->delete();
        }
        return false;
    }
    public function getOrdersByFilters(array $filters): Collection
    {
        // Start a query on the Order model
        $query = Order::query();

        // Loop through the filters and apply them to the query dynamically
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                // If the column exists in the database, apply the filter
                if (Schema::hasColumn('orders', $key)) {
                    $query->where($key, $value);
                }
            }
        }

        // You can also add pagination here if needed
        return $query->get();
    }

    // Get orders by user ID
    public function getOrdersByUserId(string $userId): Collection
    {
        return Order::where('user_id', $userId)->get();
    }

    // Get orders by user role
    public function getOrdersByRole(string $role, string $userId): Collection
    {
        return Order::whereHas('user', function ($query) use ($role, $userId) {
            $query->where('role', $role)->where('user_id', $userId);
        })->get();
    }
    // Get orders by status
    public function getOrdersByStatus(string $status): Collection
    {
        return Order::where('status', $status)->get();
    }

    // Get orders by payment status
    public function getOrdersByPaymentStatus(string $paymentStatus): Collection
    {
        return Order::where('payment_status', $paymentStatus)->get();
    }

    // Get orders by tracking number
    public function getOrdersByTrackingNumber(string $trackingNumber): Collection
    {
        return Order::where('tracking_number', $trackingNumber)->get();
    }

    // Get orders by order number
    public function getOrdersByOrderNumber(string $orderNumber): Collection
    {
        return Order::where('order_number', $orderNumber)->get();
    }

    // Get orders by specific address type (e.g., shipping or billing)
    public function getOrdersByAddress(string $type, string $address): Collection
    {
        return Order::whereHas('address', function ($query) use ($type, $address) {
            $query->where('type', $type)->where('address', $address);
        })->get();
    }

    // Get orders by shipping zone
    public function getOrdersByShippingZone(int $shippingZoneId): Collection
    {
        return Order::where('shipping_zone_id', $shippingZoneId)->get();
    }

    // Get orders for a specific vendor with optional filters
    public function getVendorOrders(int $vendorId, array $filters = []): Collection
    {
        $query = Order::where('vendor_id', $vendorId);

        foreach ($filters as $key => $value) {
            if ($key === 'status') {
                $query->where('status', $value);
            }
            // Add more filters as needed
        }

        return $query->get();
    }

    // Get orders by product ID
    public function getOrdersByProduct(int $productId): Collection
    {
        return Order::whereHas('products', function ($query) use ($productId) {
            $query->where('product_id', $productId);
        })->get();
    }

    // Get orders by order item ID
    public function getOrdersByOrderItem(int $orderItemId): Collection
    {
        return Order::whereHas('orderItems', function ($query) use ($orderItemId) {
            $query->where('order_item_id', $orderItemId);
        })->get();
    }

    // Get orders by item details
    public function getOrdersByItemDetails(array $itemFilters): Collection
    {
        $query = Order::query();

        foreach ($itemFilters as $key => $value) {
            if ($key === 'item_name') {
                $query->whereHas('orderItems', function ($query) use ($value) {
                    $query->where('name', $value);
                });
            } // Add more item filters as needed
        }

        return $query->get();
    }

    // Get orders by amount range
    public function getOrdersByAmountRange(string $type, float $minAmount, float $maxAmount): Collection
    {
        $query = Order::query();

        if ($type === 'greater_than') {
            $query->where('total_amount', '>', $minAmount);
        } elseif ($type === 'less_than') {
            $query->where('total_amount', '<', $maxAmount);
        }

        return $query->whereBetween('total_amount', [$minAmount, $maxAmount])->get();
    }

    public function predictOrder(string $userId)
{
    // Fetch the most recent 5 orders for the user
    $recentOrders = Order::where('user_id', $userId)
        ->orderBy('order_date', 'desc')
        ->limit(5)
        ->pluck('id');
    \Log::info('Recent Orders:', $recentOrders->toArray());

    // If no recent orders are found
    if ($recentOrders->isEmpty()) {
        return [
            'message' => 'No order history found for this user.',
            'predicted_products' => []
        ];
    }

    // Fetch order items related to the recent orders
    $predictedProducts = OrderItem::whereIn('order_id', $recentOrders)
        ->select('product_id', 'order_id', 'product_name', 'quantity', 'total_price')
        ->get();

    // Log the retrieved order items for debugging
    \Log::info('OrderItems with product_id:', $predictedProducts->toArray());

    // If no order items are found
    if ($predictedProducts->isEmpty()) {
        return [
            'message' => 'No products found for recent orders.',
            'predicted_products' => []
        ];
    }

    // Return the predicted products
    return [
        'message' => 'Predicted products for next order',
        'predicted_products' => $predictedProducts
    ];
}
}
