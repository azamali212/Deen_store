<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\StripPayment\StripPaymentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function () {

    Route::post('/create-customer', [StripPaymentController::class, 'createCustomer']);
    Route::get('/stripe/customer-id', [StripPaymentController::class, 'getCustomerId']);
    Route::post('/strip/update-payment-method', [StripPaymentController::class, 'updatePaymentMethod']);
    Route::get('/strip/get-payment-method', [StripPaymentController::class, 'getPaymentMethod']);
    Route::delete('/users/{user}/payment-methods', [StripPaymentController::class, 'detachAllPaymentMethods']);
    Route::post('/strip/create', [StripPaymentController::class, 'createPaymentMethod']);
    Route::post('/strip/charge', [StripPaymentController::class, 'chargeCustomer']);
});