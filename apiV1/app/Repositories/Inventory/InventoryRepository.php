<?php

namespace App\Repositories\Inventory;

use App\Models\InventoryLog;
use App\Models\InventoryStock;
use App\Models\Product_Batche;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryRepository implements InventoryRepositoryInterface
{

    public function create(array $data)
    {
        // Create Inventory
        $inventory =  InventoryStock::create($data);
        if (!$inventory) {
            throw new \Exception('Inventory creation failed.');
        }
        return $inventory;
    }

    public function update(array $data, int $id)
    {
        // Update Inventory
        $inventoryStock = InventoryStock::findOrFail($id);
        $inventoryStock->update($data);
        return $inventoryStock;
    }

    public function delete(int $id)
    {
        // Delete Inventory
        $inventoryStock = InventoryStock::findOrFail($id);
        $inventoryStock->delete();
        return true;
    }

    public function find(int $id)
    {
        return InventoryStock::with(['product', 'warehouse'])->findOrFail($id);
    }

    public function all(int $perPage = 10)
    {
        // Get all Inventories
        return InventoryStock::with(['product', 'warehouse'])->paginate($perPage);
    }

    public function search(array $data)
    {
        // Search Inventory
        return InventoryStock::whereHas('product', function ($query) use ($data) {
            if (isset($data['name'])) {
                $query->where('name', 'LIKE', '%' . $data['name'] . '%');
            }
        })->get();
    }

    public function getLogs(int $id)
    {
        // Get Inventory Logs
        $inventory = InventoryLog::where('product_id', $id)->orderByDesc('created_at')->get();
        //\Log::info($inventory);
        return $inventory;
    }

    public function log(array $data)
    {
        // Log Inventory
        $inventoryLog = InventoryLog::create($data);
        return $inventoryLog;
    }

    public function checkStockLevel(int $productId)
    {
        // Check Stock Level
        $invntory = InventoryStock::where('product_id', $productId)->sum('quantity');
        //dd($invntory);
        return $invntory;
    }

    public function autoRestock($productId, $restockAmount)
    {
        return DB::transaction(function () use ($productId, $restockAmount) {
            // Fetch inventory record for the given product ID
            $inventory = InventoryStock::where('product_id', $productId)->first();

            if (!$inventory) {
                return [
                    'success' => false,
                    'message' => 'Inventory not found for the given product.'
                ];
            }

            // Check if stock is sufficient based on the threshold
            if ($inventory->quantity > $inventory->auto_restock_threshold) {
                return [
                    'success' => false,
                    'message' => 'Stock level is sufficient. Auto restock not needed.',
                    'data' => $inventory
                ];
            }

            // Ensure restockAmount is valid and positive
            if ($restockAmount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid restock amount. It must be greater than 0.'
                ];
            }

            // Update the inventory quantity by incrementing the current amount by the restock amount
            $inventory->increment('quantity', $restockAmount);

            // Log the restock action
            InventoryLog::create([
                'product_id' => $productId,
                'type' => 'restock',
                'quantity' => $restockAmount,
                'description' => 'Auto restocked to maintain inventory level.'
            ]);

            // Return success response with the updated inventory details
            return [
                'success' => true,
                'message' => 'Inventory auto-restocked successfully.',
                'data' => $inventory->fresh()
            ];
        });
    }

    public function getStockNeeded(int $productId)
    {
        // Check if the product exists in inventory stock
        $inventory = InventoryStock::where('product_id', $productId)->first();

        if (!$inventory) {
            return [
                'success' => false,
                'message' => 'Invalid product ID. The product does not exist in inventory.',
                'data' => []
            ];
        }

        // Check if stock is below or equal to the threshold
        if ($inventory->quantity <= $inventory->auto_restock_threshold) { // FIXED CONDITION
            return [
                'success' => true,
                'message' => 'This product requires restocking.',
                'data' => $inventory
            ];
        }

        return [
            'success' => false,
            'message' => 'Stock level is sufficient. Auto restock not needed.',
            'data' => []
        ];
    }

    public function trackBatchExpiry(int $productId)
    {
        // Track Batch Expiry, check for products that are already expired
        return Product_Batche::where('product_id', $productId)
            ->whereDate('expiry_date', '<', Carbon::now())  // Changed to check for expired products
            ->orderBy('expiry_date')
            ->get();
    }

    /** 
     * public function createProductBundle(array $data)
    {
        // Create Product Bundle
    }
     */


    public function forecastSales(array $data)
    {
        return Sale::whereBetween('sale_date', [$data['start_date'], $data['end_date']])
            ->when(isset($data['product_id']), function ($query) use ($data) {
                $query->where('product_id', $data['product_id']);
            })
            ->when(isset($data['vendor_id']), function ($query) use ($data) {
                $query->where('vendor_id', $data['vendor_id']);
            })
            ->when(isset($data['status']), function ($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->sum('quantity');
    }

    public function allocateStockForOrder(int $productId, int $quantity)
    {
        return DB::transaction(function () use ($productId, $quantity) {
            $inventory = InventoryStock::where('product_id', $productId)->first();

            if (!$inventory || $inventory->quantity < $quantity) {
                throw new \Exception('Not enough stock available.');
            }

            // Deduct the allocated quantity
            $inventory->decrement('quantity', $quantity);

            // Log the allocation
            InventoryLog::create([
                'product_id' => $productId,
                'type' => 'sale',
                'quantity' => $quantity,
                'description' => 'Stock allocated for order'
            ]);

            return [
                'success' => true,
                'message' => 'Stock allocated successfully.',
                'data' => $inventory->fresh()
            ];
        });
    }

    public function transferStock(int $fromWarehouseId, int $toWarehouseId, int $productId, int $quantity)
    {
        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $productId, $quantity) {
            $fromStock = InventoryStock::where('warehouse_id', $fromWarehouseId)->where('product_id', $productId)->first();
            $toStock = InventoryStock::where('warehouse_id', $toWarehouseId)->where('product_id', $productId)->first();

            if (!$fromStock || $fromStock->quantity < $quantity) {
                return response()->json(['error' => 'Not enough stock in source warehouse'], 400);
            }

            // Deduct stock from source warehouse
            $fromStock->decrement('quantity', $quantity);

            // Add stock to destination warehouse
            if ($toStock) {
                $toStock->increment('quantity', $quantity);
            } else {
                InventoryStock::create([
                    'product_id' => $productId,
                    'warehouse_id' => $toWarehouseId,
                    'quantity' => $quantity,
                ]);
            }

            // Log transfer
            InventoryLog::create([
                'product_id' => $productId,
                'type' => 'adjustment',
                'quantity' => $quantity,
                'description' => "Stock transferred from Warehouse {$fromWarehouseId} to Warehouse {$toWarehouseId}",
            ]);

            return response()->json(['message' => 'Stock transferred successfully'], 200);
        });
    }

    public function getWarehouseStock(int $warehouseId)
    {
        // Get Warehouse Stock
    }

    public function generateInventoryReport(array $filters)
    {
        // Generate Inventory Report
    }

    public function createSupplier(array $data)
    {
        // Create Supplier
    }

    public function updateSupplier(int $supplierId, array $data)
    {
        // Update Supplier
    }

    public function deleteSupplier(int $supplierId)
    {
        // Delete Supplier
    }

    public function getSupplier(int $supplierId)
    {
        // Get Supplier
    }

    public function listSuppliers(array $filters)
    {
        // List Suppliers
    }

    public function createPurchaseOrder(array $data)
    {
        // Create Purchase Order
    }

    public function updatePurchaseOrderStatus(int $purchaseOrderId, string $status)
    {
        //
    }

    public function deletePurchaseOrder(int $purchaseOrderId)
    {
        // Delete Purchase Order
    }

    public function getPurchaseOrder(int $purchaseOrderId)
    {
        // Get Purchase Order
    }

    public function listPurchaseOrders(array $filters)
    {
        // List Purchase Orders
    }

    public function receiveStockFromPurchaseOrder(int $purchaseOrderId)
    {
        // Receive Stock From Purchase Order
    }
}
