<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\CustomerAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/customer/auth')->name('customer.auth.')->group(function (): void {

        Route::post('/register', [CustomerAuthController::class, 'register'])->name('register');

        Route::post('/login', [CustomerAuthController::class, 'login'])->name('login');

        Route::post('/verify-otp', [CustomerAuthController::class, 'verifyOtp'])->name('verify-otp');

        Route::middleware('auth:sanctum')->group(function (): void {
                Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
            });
    });