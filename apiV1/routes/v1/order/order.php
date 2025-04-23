<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Order\OrderTRackingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {

    //Crud operations for order
    Route::get('/order', [OrderController::class, 'index']);
    Route::get('/orders/prioritize', [OrderController::class, 'prioritizeOrders'])->name('orders.prioritize');
    Route::get('/order/{id}', [OrderController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/order', [OrderController::class, 'store']);
    Route::put('/order/{orderId}', [OrderController::class, 'update']);
    Route::delete('/order/{orderId}', [OrderController::class, 'destroy']);
    Route::get('/filter', [OrderController::class, 'orderFilters']);
    Route::get('/filter/{userId}', [OrderController::class, 'getOrdersByUserId']);
    Route::get('/filter/{role}/{userId}', [OrderController::class, 'getOrdersByRole']);
    Route::get('/predict/{userId}', [OrderController::class, 'predictOrder']);
    Route::patch('/order/{orderId}/status', [OrderController::class, 'changeStatus'])->name('orders.changeStatus');

    Route::get('orders/delayed', [OrderController::class, 'getDelayedOrders']);
    Route::post('orders/{orderId}/delayed', [OrderController::class, 'markOrderAsDelayed']);
    Route::post('orders/{orderId}/escalate', [OrderController::class, 'escalateOrder']);
    Route::post('orders/escalate-delayed', [OrderController::class, 'escalateDelayedOrders']);
    Route::post('order/{orderId}/fraud', [OrderController::class, 'detectFraudulentOrder']);
    Route::post('order/{orderId}/process', [OrderController::class, 'automateOrderProcessing']);

    // Order tracking
    Route::get('/order/{orderId}/tracking', [OrderTRackingController::class, 'latestStatus'])->name('orders.latestStatus');
    Route::post('/order/{orderId}/tracking', [OrderTRackingController::class, 'createTracking'])->name('orders.createTracking');
    Route::get('/order/{orderId}/tracking/history', [OrderTRackingController::class, 'getTrackingHistory'])->name('orders.getTrackingHistory');
    Route::put('/order/{orderId}/tracking/{trackingId}', [OrderTRackingController::class, 'updateTracking'])->name('orders.updateTracking');
    Route::delete('/order/{orderId}/tracking/{trackingId}', [OrderTRackingController::class, 'deleteTracking'])->name('orders.deleteTracking');
    Route::post('/order/{orderId}/tracking/bulk-update', [OrderTRackingController::class, 'bulkUpdateTracking'])->name('orders.bulkUpdateTracking');
    Route::post('/order/{orderId}/tracking/bulk-update-status', [OrderTRackingController::class, 'bulkUpdateStatuses'])->name('orders.bulkUpdateStatuses');
    Route::get('/order/by-date-range', [OrderTRackingController::class, 'byDateRange']);
    Route::get('/order/by-location', [OrderTRackingController::class, 'byLocation']);
    Route::get('/order/has-status/{orderId}/{status}', [OrderTRackingController::class, 'hasStatus']);
    Route::get('/order/latest-location/{orderId}', [OrderTRackingController::class, 'latestLocation']);
    Route::get('/order/delivery-stats/{managerId}', [OrderTRackingController::class, 'deliveryStatsByManager']);
    Route::post('/order/search', [OrderTRackingController::class, 'search']);
    Route::post('/order/sync-courier', [OrderTRackingController::class, 'syncWithCourier']);
});
