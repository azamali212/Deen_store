<?php

namespace App\Repositories\Order;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface{

   // CRUD Operations
  

   public function getAllOrders(): LengthAwarePaginator;
    
    public function getOrderById(int $id): ?Order;

    //public function escalateDelayedOrders();
    
    public function createOrder(array $data): Order;
    
    public function updateOrder(int $orderId, array $data): bool;
    
    public function deleteOrder(int $orderId): bool;
    
    public function addOrderItems(int $orderId, array $items): bool;
    
    public function updateOrderItem(int $orderItemId, array $data): bool;
    
    public function removeOrderItem(int $orderItemId): bool;

    public function changeOrderStatus(int $orderId, string $status): bool;

    public function predictOrderTrends(): array; // New: Predict order trends

    public function detectFraudulentOrder(int $orderId): bool; // New: Fraud detection

    public function automateOrderProcessing(int $orderId): bool; // New: Order automation

    public function getOrdersByFilters(array $filters): Collection;

    public function prioritizeOrders(): array;

    public function escalateDelayedOrders(): bool;
    public function escalateOrder(Order $order);
    public function markOrderAsDelayed(Order $order);
    public function getDelayedOrders(): Collection;

    public function getHighValueOrders(): Collection;

  

    public function archiveOldOrders(): bool;
}