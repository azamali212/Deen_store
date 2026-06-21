<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin/auth')->name('admin.auth.')->group(function (): void {

        Route::post('/login', [AdminAuthController::class, 'login'])->name('login');

        Route::post('/verify-otp', [AdminAuthController::class, 'verifyOtp'])->name('verify-otp');

        Route::middleware(['auth:sanctum','role:super_admin|admin',])->group(function (): void {

            Route::post('/register', [AdminAuthController::class, 'register'])->name('register');

            Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        });
    });