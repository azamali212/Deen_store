<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Order\OrderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {

    //Crud operations for order
    Route::get('/order',[OrderController::class, 'index']);
    Route::get('/order/{id}',[OrderController::class, 'show']);
    Route::post('/order',[OrderController::class, 'store']);
    Route::put('/order/{id}',[OrderController::class, 'update']);
    Route::delete('/order/{id}',[OrderController::class, 'destroy']);
    Route::get('/filter',[OrderController::class, 'orderFilters']);
    Route::get('/filter/{userId}',[OrderController::class, 'getOrdersByUserId']);
    Route::get('/filter/{role}/{userId}',[OrderController::class, 'getOrdersByRole']);
    Route::get('/predict/{userId}',[OrderController::class, 'predictOrder']);
});