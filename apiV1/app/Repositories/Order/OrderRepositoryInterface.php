<?php

namespace App\Repositories\Order;

interface OrderRepositoryInterface{

   // CRUD Operations
   public function all(int $perPage = 15);
   public function show(int $id);
   public function create(array $data);
   public function update(array $data, int $id);
   public function delete(int $id);

   // Order Queries
   public function getOrdersByFilters(array $filters);
   public function getOrdersByUserId(string $userId);
   public function getOrdersByRole(string $role, string $userId);
   public function getOrdersByStatus(string $status);
   public function getOrdersByPaymentStatus(string $paymentStatus);
   public function getOrdersByTrackingNumber(string $trackingNumber);
   public function getOrdersByOrderNumber(string $orderNumber);

   // Address & Shipping Queries
   public function getOrdersByAddress(string $type, string $address);
   public function getOrdersByShippingZone(int $shippingZoneId);

   // Vendor-Specific Queries
   public function getVendorOrders(int $vendorId, array $filters = []);
   
   // Order Item Queries
   public function getOrdersByProduct(int $productId);
   public function getOrdersByOrderItem(int $orderItemId);
   public function getOrdersByItemDetails(array $itemFilters);

   // Financial Queries
   public function getOrdersByAmountRange(string $type, float $minAmount, float $maxAmount);

   public function predictOrder(string $userId);

}