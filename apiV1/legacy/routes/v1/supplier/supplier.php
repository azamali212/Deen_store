<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Supplier\SupplierController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    Route::post('/supplier/create', [SupplierController::class, 'store']);
    Route::put('/supplier/update/{id}', [SupplierController::class, 'update']);
    Route::delete('/supplier/delete/{id}', [SupplierController::class, 'destroy']);
    Route::get('/supplier/show/{id}', [SupplierController::class, 'show']);
    Route::get('/supplier/index', [SupplierController::class, 'index']);

    //Other routes
    Route::get('/supplier/filters', [SupplierController::class, 'filters']);
    Route::get('/supplier/performance/{supplierId}', [SupplierController::class, 'evaluateSupplierPerformance']);
    Route::post('/supplier/{supplierId}/terminate', [SupplierController::class, 'terminateContract']);
    Route::get('/supplier/top-performers/{limit}', [SupplierController::class, 'getTopPerformingSuppliers']);
    Route::post('/supplier/assign-supplier-to-category', [SupplierController::class, 'assignSupplierToCategory']);
    Route::get('/supplier/{supplierId}/delivery-status', [SupplierController::class, 'trackSupplierDeliveryStatus']);
    Route::get('/supplier/{supplierId}/monthly-report', [SupplierController::class, 'generateSupplierReport']);
    Route::get('/supplier-reports/export', [SupplierController::class, 'exportSupplierReport']);
    Route::post('/supplier/{supplierId}/mark-as-preferred', [SupplierController::class, 'markSupplierAsPreferred']);
    Route::post('/supplier/check-stock', [SupplierController::class, 'checkStockAvailability']);

    //More 
    //Route::get('/supplier/{supplierId}/payment-history', [SupplierController::class, 'getSupplierPaymentHistory']);
    Route::get('/supplier/supplier-payment-history', [SupplierController::class, 'generateSupplierPaymentHistoryReport']);
    Route::get('/supplier/export-supplier-payment-history', [SupplierController::class, 'exportSupplierPaymentHistoryReport']);
    Route::post('/supplier/{supplierId}/blacklist', [SupplierController::class, 'blacklistSupplier']);
    Route::post('/supplier/{supplierId}/unblacklist', [SupplierController::class, 'unblacklistSupplier']);
    Route::get('/supplier/blacklisted', [SupplierController::class, 'getBlacklistedSuppliers']);
    Route::post('/supplier/{supplierId}/process-payment', [SupplierController::class, 'processSupplierPayment']);
    Route::get('/supplier/payment/{paymentId}', [SupplierController::class, 'getSupplierPaymentDetails']);
    Route::get('/supplier/category/{categoryId}', [SupplierController::class, 'getSuppliersByCategory']);
    Route::get('/supplier/active-contracts', [SupplierController::class, 'getSuppliersWithActiveContracts']);
    Route::post('/supplier/{supplierId}/update-contract-status', [SupplierController::class, 'updateSupplierContractStatus']);
    Route::get('/supplier/pending-contracts', [SupplierController::class, 'getSuppliersPendingContracts']);
    Route::get('/supplier/terminated-contracts', [SupplierController::class, 'getContractTerminatedSuppliers']);
});
