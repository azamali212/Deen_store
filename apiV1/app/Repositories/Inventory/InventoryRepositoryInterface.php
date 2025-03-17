<?php 
namespace App\Repositories\Inventory;

interface InventoryRepositoryInterface
{
    public function create(array $data);

    public function update(array $data, int $id);

    public function delete(int $id);

    public function find(int $id);

    public function all();

    public function paginate(int $perPage);

    public function search(array $data);

    public function getLogs(int $id);

    public function log(array $data);

    // New methods for optimization and business logic
    public function checkStockLevel(int $productId);

    public function autoRestock(int $productId);

    public function trackBatchExpiry(int $productId);

    public function createProductBundle(array $data);

    public function forecastSales(array $data);

    public function allocateStockForOrder(int $productId, int $quantity);

    public function transferStock(int $fromWarehouseId, int $toWarehouseId, int $productId, int $quantity);

    public function getWarehouseStock(int $warehouseId);

    public function generateInventoryReport(array $filters);
}