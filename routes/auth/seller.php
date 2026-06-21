<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\SellerAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/seller/auth')->name('seller.auth.')->group(function (): void {

        Route::post('/register', [SellerAuthController::class, 'register'])->name('register');

        Route::post('/login', [SellerAuthController::class, 'login'])->name('login');

        Route::post('/verify-otp', [SellerAuthController::class, 'verifyOtp'])->name('verify-otp');

        Route::middleware('auth:sanctum')->group(function (): void {
                Route::post('/logout', [SellerAuthController::class, 'logout'])->name('logout');
            });
    });