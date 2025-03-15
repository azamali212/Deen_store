<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderRequest;
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
        $perPage = $request->input('per_page', 15);
        $orders = $this->orderRepository->all($perPage);
        return response()->json($orders);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderRepository->show($id);
        return response()->json($order);
    }

    public function store(OrderRequest $request): JsonResponse
    {
        $data = $request->validated(); // Only validated data
        $order = $this->orderRepository->create($data);

        return response()->json([
            'message' => 'Order created successfully',
            'order'   => $order
        ], 201);
    }

    public function update(OrderRequest $request, int $id): JsonResponse
    {
        $data = $request->validated(); // Only validated data
        $order = $this->orderRepository->update($data, $id);
    
        return response()->json([
            'message' => 'Order updated successfully',
            'order'   => $order // Return the updated order object
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $isDeleted = $this->orderRepository->delete($id);
        if ($isDeleted) {
            return response()->json([
                'message' => 'Order deleted successfully'
            ]);
        }
        return response()->json([
            'message' => 'Order not found'
        ], 404);
    }

    public function orderFilters(Request $request)
    {
        // Logic to handle order filters
        $filters = $request->only(['payment_status', 'order_status', 'tracking_number']); // Add other filter parameters here
        
        $orders = $this->orderRepository->getOrdersByFilters($filters);  // Use the filtering logic method
        
        return response()->json($orders);
    }

    public function getOrdersByUserId(string $userId): JsonResponse
    {
        $orders = $this->orderRepository->getOrdersByUserId($userId);
        return response()->json($orders);
    }

    public function getOrdersByRole(string $role, string $userId): JsonResponse
    {
        $orders = $this->orderRepository->getOrdersByRole($role, $userId);
        return response()->json($orders);
    }

    public function predictOrder(string $userId): JsonResponse
    {
        // Logic to predict the next order for a customer
        $order = $this->orderRepository->predictOrder($userId);
        return response()->json($order);
    }
}
