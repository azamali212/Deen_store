<?php

namespace App\Repositories\Inventory;

use App\Models\InventoryLog;
use App\Models\InventoryStock;
use App\Models\Product_Batche;
use App\Models\Sale;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventoryRepository implements InventoryRepositoryInterface
{

    public function create(array $data)
    {
        // Create Inventory
        $inventory =  InventoryStock::create($data);
        if (!$inventory) {
            throw new Exception('Inventory creation failed.');
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

    public function findInventory($inventoryId)
    {
        return InventoryStock::with(['product', 'warehouse'])->findOrFail($inventoryId);
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
        // Use caching for optimization (store stock data for 10 minutes)
        return Cache::remember("warehouse_stock_{$warehouseId}", 600, function () use ($warehouseId) {
            return InventoryStock::where('warehouse_id', $warehouseId)
                ->with(['product' => function ($query) {
                    $query->select('id', 'name', 'sku', 'category_id');
                }])
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($stock) {
                    return [
                        'product_id' => $stock->product_id,
                        'product_name' => $stock->product->name,
                        'sku' => $stock->product->sku,
                        'category' => optional($stock->product->category)->name ?? 'Uncategorized',
                        'warehouse_id' => $stock->warehouse_id,
                        'available_stock' => $stock->quantity,
                        'last_updated' => $stock->updated_at->format('Y-m-d H:i:s'),
                    ];
                });
        });
    }

    public function generateInventoryReport(array $filters, int $perPage = 20)
    {
        try {
            // Log the filters and perPage value for debugging
            \Log::info('Filters:', $filters);
            \Log::info('PerPage:', [$perPage]);

            // Cast the filter values to integers or null if not provided
            $productId = isset($filters['product_id']) ? (int) $filters['product_id'] : null;
            $warehouseId = isset($filters['warehouse_id']) ? (int) $filters['warehouse_id'] : null;
            $dateRange = isset($filters['date_range']) ? $filters['date_range'] : null;

            return Cache::remember("inventory_report_" . md5(json_encode($filters)), 600, function () use ($productId, $warehouseId, $filters, $perPage) {
                $query = InventoryStock::select('id', 'product_id', 'warehouse_id', 'quantity', 'updated_at')
                    ->with([
                        'product:id,name,sku',
                        'warehouse:id,name'
                    ])
                    ->when(!empty($productId), fn($q) => $q->where('product_id', $productId))
                    ->when(!empty($warehouseId), fn($q) => $q->where('warehouse_id', $warehouseId))
                    ->when(!empty($filters['date_range']) && isset($filters['date_range']['from'], $filters['date_range']['to']), fn($q) => $q->whereBetween('created_at', [$filters['date_range']['from'], $filters['date_range']['to']]))
                    ->orderBy('updated_at', 'desc');

                $results = $query->paginate($perPage);

                if ($results->isEmpty()) {
                    \Log::info('No results found for the given filters.');
                    return response()->json(['message' => 'No records found.'], 404);
                }

                return $results;
            });
        } catch (\Throwable $e) {
            \Log::error('Error generating inventory report: ' . $e->getMessage(), [
                'filters' => $filters,
                'exception' => $e
            ]);
            return response()->json(['error' => 'Unable to generate report.'], 500);
        }
    }

    /**
     * Export inventory report to a CSV file.
     */
    public function exportInventoryReport(array $filters)
    {
        try {
            \Log::info('Exporting inventory report with filters: ', $filters);
    
            // Generate the report data
            $reportData = $this->generateInventoryReport($filters, 1000);
    
            // Log if no data is returned
            if ($reportData->isEmpty()) {
                \Log::warning('No data found for the given filters');
                throw new \Exception('No data found for the report.');
            }
    
            $csvFileName = 'inventory_report_' . now()->format('Ymd_His') . '.csv';
            $csvFilePath = storage_path('app/reports/' . $csvFileName);
    
            // Ensure the directory exists and is writable
            $directoryPath = storage_path('app/reports');
            if (!is_dir($directoryPath)) {
                \Log::info('Creating directory: ' . $directoryPath);
                mkdir($directoryPath, 0775, true);  // Create the directory if it doesn't exist
            }
    
            // Open file for writing
            $file = fopen($csvFilePath, 'w');
            if (!$file) {
                throw new \Exception('Failed to open file for writing.');
            }
    
            // Add header row
            fputcsv($file, ['Product Name', 'SKU', 'Warehouse', 'Available Stock', 'Last Updated']);
    
            // Write the data rows to the CSV file
            foreach ($reportData as $stock) {
                fputcsv($file, [
                    $stock->product->name ?? 'N/A',
                    $stock->product->sku ?? 'N/A',
                    $stock->warehouse->name ?? 'N/A',
                    $stock->quantity,
                    $stock->updated_at->format('Y-m-d H:i:s'),
                ]);
            }
    
            fclose($file);
    
            // Log successful export
            \Log::info("Report exported successfully to: " . $csvFilePath);
    
            // Return the file path for download
            return $csvFilePath;
        } catch (\Exception $e) {
            // Log the exception for better debugging
            \Log::error('Error in exportInventoryReport: ' . $e->getMessage());
            return false; // Return false if export fails
        }
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
