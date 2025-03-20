<?php 
namespace App\Repositories\Inventory;

interface InventoryRepositoryInterface
{
    // Inventory CRUD Operations
    public function create(array $data);
    public function update(array $data, int $id);
    public function delete(int $id);
    public function find(int $id);
    public function all(int $perPage);
    public function search(array $data);

    // Inventory Logs
    public function getLogs(int $id);
    public function log(array $data);

    // Stock Management
    public function checkStockLevel(int $productId);
    public function autoRestock(int $productId ,int $restockAmount);

    public function getStockNeeded(int $productId);
    public function trackBatchExpiry(int $productId);
    //public function createProductBundle(array $data);
    public function forecastSales(array $data);
    public function allocateStockForOrder(int $productId, int $quantity);
    public function transferStock(int $fromWarehouseId, int $toWarehouseId, int $productId, int $quantity);
    public function getWarehouseStock(int $warehouseId);
    public function generateInventoryReport(array $filters);

    // Supplier Management
    public function createSupplier(array $data);
    public function updateSupplier(int $supplierId, array $data);
    public function deleteSupplier(int $supplierId);
    public function getSupplier(int $supplierId);
    public function listSuppliers(array $filters);

    // Purchase Order Management
    public function createPurchaseOrder(array $data);
    public function updatePurchaseOrderStatus(int $purchaseOrderId, string $status);
    public function deletePurchaseOrder(int $purchaseOrderId);
    public function getPurchaseOrder(int $purchaseOrderId);
    public function listPurchaseOrders(array $filters);
    public function receiveStockFromPurchaseOrder(int $purchaseOrderId);
}