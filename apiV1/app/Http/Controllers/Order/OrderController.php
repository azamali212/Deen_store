<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderRequest;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Repositories\Order\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepositoryInterface)
    {
        $this->orderRepository = $orderRepositoryInterface;
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderRepository->getAllOrders();
        return response()->json($orders);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderRepository->getOrderById($id);
        return response()->json($order);
    }

    public function store(OrderRequest $request)
    {
        $data = $request->validated();
        $order = $this->orderRepository->createOrder($data);
        return response()->json($order);
    }

    public function update(OrderRequest $request, int $orderId): JsonResponse
    {
        $data = $request->validated();
        $order = $this->orderRepository->updateOrder($orderId, $data);
        return response()->json($order);
    }

    public function destroy(int $orderId): JsonResponse
    {
        $order = $this->orderRepository->deleteOrder($orderId);
        return response()->json($order);
    }
    public function orderFilters(Request $request): JsonResponse
    {
        $orders = $this->orderRepository->getAllOrders();
        return response()->json($orders);
    }

    public function changeStatus(OrderRequest $request, int $orderId): JsonResponse
    {
        $data = $request->validated();

        $updated = $this->orderRepository->changeOrderStatus($orderId, $data['status']);

        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => [
                    'order_id' => $orderId,
                    'order_status' => $data['status']
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to update order status.',
        ], 500);
    }

    public function prioritizeOrders(Request $request): JsonResponse
    {
        $orders = $this->orderRepository->prioritizeOrders();

        return response()->json([
            'success' => true,
            'message' => 'Prioritized orders retrieved successfully.',
            'data' => [
                'vip_customers' => OrderResource::collection($orders['vip_customers']),
                'high_value_orders' => OrderResource::collection($orders['high_value_orders']),
            ]
        ]);
    }

    public function getDelayedOrders(): JsonResponse
    {
        $delayedOrders = $this->orderRepository->getDelayedOrders();
    
        // Log the resulting delayed orders
        \Log::info('Delayed Orders:', $delayedOrders->toArray());
    
        return response()->json($delayedOrders);
    }
    // Mark an order as delayed
    public function markOrderAsDelayed(int $orderId)
    {
        $order = Order::findOrFail($orderId);
        $this->orderRepository->markOrderAsDelayed($order);
        return response()->json(['message' => 'Order marked as delayed.']);
    }

    // Escalate an order
    public function escalateOrder(int $orderId)
    {
        $order = Order::findOrFail($orderId);
        $this->orderRepository->escalateOrder($order);
        return response()->json(['message' => 'Order escalated.']);
    }

    // Handle escalation process for delayed orders
    public function escalateDelayedOrders()
    {
        $escalated = $this->orderRepository->escalateDelayedOrders();
        if ($escalated) {
            return response()->json(['message' => 'Delayed orders have been escalated.']);
        } else {
            return response()->json(['message' => 'No delayed orders to escalate.']);
        }
    }

    public function detectFraudulentOrder(int $orderId)
    {
        $isFraudulent = $this->orderRepository->detectFraudulentOrder($orderId);
        return response()->json(['is_fraudulent' => $isFraudulent]);
    }

    /**
     * Automate the order processing and check for fraud.
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function automateOrderProcessing(int $orderId)
    {
        $isProcessed = $this->orderRepository->automateOrderProcessing($orderId);

        if ($isProcessed) {
            return response()->json(['message' => 'Order processed successfully']);
        }

        return response()->json(['message' => 'Order processing failed'], 400);
    }
}
