<?php


use App\Http\Controllers\InventorySystem\InventoryController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    Route::get('/inventory', [InventoryController::class, 'getAll']);
    Route::get('/inventory/{id}', [InventoryController::class, 'getSingle']);
    Route::post('/inventory/create', [InventoryController::class, 'create']);
    Route::put('/inventory/update/{id}', [InventoryController::class, 'update']);
    Route::delete('/inventory/delete/{id}', [InventoryController::class, 'delete']);

    // Inventory Other Routes
    Route::get('/inventory/logs/{id}', [InventoryController::class, 'getLogs']);
    Route::post('/inventory/log', [InventoryController::class, 'log']);
    Route::get('/inventory/stock-level/{productId}', [InventoryController::class, 'checkStockLevel']);
    Route::post('/inventory/auto-restock/{productId}', [InventoryController::class, 'autoRestock']);
    Route::get('/inventory/stock-needed/{productId}', [InventoryController::class, 'getStockNeeded']);
    Route::get('/inventory/track-batch-expiry/{productId}', [InventoryController::class, 'trackBatchExpiry']);
    Route::post('/inventory/forecast-sales', [InventoryController::class, 'forecastSales']);
    Route::post('/inventory/allocatStockOrder/{productId}',[InventoryController::class,'allocateStockForOrder']);
    Route::post('/inventory/transfer/{fromWarehouseId}/{toWarehouseId}', [InventoryController::class, 'transferStock']);
});
