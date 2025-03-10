<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Gift\CouponsController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    
    Route::post('/gifts/coupons', [CouponsController::class, 'createCoupon']);

    // Update an existing coupon
    Route::put('/gifts/coupons/{id}', [CouponsController::class, 'updateCoupon']);
    // Get all coupons for a specific user
    Route::get('/gifts/coupons/user/{user_id}', [CouponsController::class, 'getCouponsByUser']);

    // Apply a coupon to an order
    Route::post('/gifts/coupons/apply', [CouponsController::class, 'applyCoupon']);

    // Check if a coupon is valid
    Route::post('/gifts/coupons/check-validity', [CouponsController::class, 'checkCouponValidity']);
    Route::post('/gifts/coupons/check-expiry', [CouponsController::class, 'isExpire']);
    Route::post('/gifts/coupons/check-usage', [CouponsController::class, 'checkUsagLimit']);

    // Create a new coupon
    Route::post('/gifts/coupons', [CouponsController::class, 'createCoupon']);

    // Update an existing coupon
    Route::put('/gifts/coupons/{id}', [CouponsController::class, 'updateCoupon']);

    // Delete a coupon
    Route::delete('/gifts/coupons/{id}', [CouponsController::class, 'deleteCoupon']);
});
