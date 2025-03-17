<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Order\OrderController;
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
});
