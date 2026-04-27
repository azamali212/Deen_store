<?php

namespace App\Http\Controllers\InventorySystem;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryRequest;
use App\Models\InventoryStock;
use App\Repositories\Inventory\InventoryRepositoryInterface;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    protected $inventoryRepository;

    public function __construct(InventoryRepositoryInterface $inventoryRepositoryInterface)
    {
        $this->inventoryRepository = $inventoryRepositoryInterface;
    }

    public function getAll(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $inventory = $this->inventoryRepository->all($perPage);
        return response()->json([
            'success' => true,
            'data' => $inventory
        ], 200);
    }

    public function getSingle(Request $request, $inventoryId)
    {
        $inventory = $this->inventoryRepository->findInventory($inventoryId);
        return response()->json([
            'success' => true,
            'data' => $inventory
        ], 200);
    }

    public function create(InventoryRequest $request)
    {
        $data = $request->validated();
        $inventory = $this->inventoryRepository->create($data);
        return response()->json([
            'success' => true,
            'data' => $inventory
        ], 201);
    }

    public function update(InventoryRequest $request, $id)
    {
        $data = $request->validated();
        $inventory = $this->inventoryRepository->update($data, $id);
        return response()->json([
            'success' => true,
            'data' => $inventory,
            'message' => 'Inventory updated successfully.'
        ], 200);
    }

    public function delete(Request $request, $id)
    {
        $this->inventoryRepository->delete($id);
        return response()->json([
            'success' => true,
            'message' => 'Inventory deleted successfully.'
        ], 200);
    }

    // Other Inventory Management Functions
    public function getLogs(Request $request, $id)
    {
        $logs = $this->inventoryRepository->getLogs($id);
        return response()->json([
            'success' => true,
            'data' => $logs
        ], 200);
    }

    public function log(Request $request)
    {
        $data = $request->all();
        $log = $this->inventoryRepository->log($data);
        return response()->json([
            'success' => true,
            'data' => $log
        ], 201);
    }

    public function checkStockLevel(Request $request, $productId)
    {
        $stockLevel = $this->inventoryRepository->checkStockLevel($productId);
        return response()->json([
            'success' => true,
            'data' => $stockLevel
        ], 200);
    }

    public function autoRestock(Request $request, $productId)
    {
        // Fetch inventory record for the given product ID
        $inventory = InventoryStock::where('product_id', $productId)->first();

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory not found for the given product.'
            ], 404);
        }

        // Get the restock amount from the request body
        // Default to twice the auto_restock_threshold if not provided
        $restockAmount = $request->input('restock_amount', max(50, $inventory->auto_restock_threshold * 2));

        // Call the repository method to handle auto restocking
        $response = $this->inventoryRepository->autoRestock($productId, $restockAmount);

        return response()->json($response, $response['success'] ? 200 : 400);
    }

    public function getStockNeeded(Request $request, int $productId)
    {
        $response = $this->inventoryRepository->getStockNeeded($productId);

        // Return 404 if the product ID is invalid
        $statusCode = $response['success'] ? 200 : ($response['message'] === 'Invalid product ID. The product does not exist.' ? 404 : 200);

        return response()->json($response, $statusCode);
    }

    public function trackBatchExpiry(Request $request, int $productId)
    {
        // Call the repository method to track batch expiry
        $expiredBatches = $this->inventoryRepository->trackBatchExpiry($productId);

        // Check if any expired batches are found
        if ($expiredBatches->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No expired products found.'
            ], 200);
        }

        // If expired batches are found, return the appropriate response
        return response()->json([
            'success' => false,
            'message' => 'Product has been expired',
            'data' => $expiredBatches
        ], 404);
    }
    public function forecastSales(Request $request)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'product_id' => 'nullable|exists:products,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'status' => 'nullable|string',
        ]);

        // Call the repository method to get the forecasted sales
        $totalSales = $this->inventoryRepository->forecastSales($validatedData);

        // Return the result
        return response()->json(['total_sales' => $totalSales], 200);
    }

    public function allocateStockForOrder(Request $request, int $productId)
    {
        $quantity = $request->input('quantity');

        try {
            // Call the repository method to allocate stock
            $response = $this->inventoryRepository->allocateStockForOrder($productId, $quantity);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function transferStock(Request $request, int $fromWarehouseId, int $toWarehouseId)
    {
        // Validate the input
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');

        // Call the repository method to transfer stock
        $response = $this->inventoryRepository->transferStock($fromWarehouseId, $toWarehouseId, $productId, $quantity);

        return $response;  // The response is returned from the repository
    }

    public function getWarehouseStock(Request $request, int $warehouseId)
    {
        $response = $this->inventoryRepository->getWarehouseStock($warehouseId);
        //dd($response);

        return response()->json([
            'success' => true,
            'message' => 'Data retrieved successfully.',
            'data' => $response
        ], 200);
    }

    public function generateInventoryReport(Request $request)
    {
        try {
            $filters = $request->only(['product_id', 'warehouse_id', 'date_range']);
            $perPage = (int) $request->input('per_page', 20);

            $report = $this->inventoryRepository->generateInventoryReport($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            \Log::error("Error generating inventory report: " . $e->getMessage());
            return response()->json(['error' => 'Unable to generate report.'], 500);
        }
    }
    /**
     * Export inventory report as a CSV file.
     */
    public function exportInventoryReport(Request $request)
    {
        try {
            $filters = $request->only(['product_id', 'warehouse_id', 'date_range']);

            // Log filters for debugging
            \Log::info('Export Filters: ', $filters);

            $filePath = $this->inventoryRepository->exportInventoryReport($filters);

            if (!$filePath) {
                throw new \Exception('File path is empty.');
            }

            // Return the generated file for download
            return response()->download($filePath);
        } catch (\Exception $e) {
            \Log::error("Error exporting inventory report: " . $e->getMessage());
            return response()->json(['error' => 'Unable to export report.'], 500);
        }
    }
}
