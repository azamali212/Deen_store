<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\CustomerAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/customer/auth')->name('customer.auth.')->group(function (): void {

        // Public — no account/session exists yet at these steps, so none
        // of these can ever sit behind auth:sanctum. Mirrors admin.php's
        // placement of the same routes.
        Route::post('/register', [CustomerAuthController::class, 'registerSelf'])
            ->name('register');

        Route::post('/login', [CustomerAuthController::class, 'login'])->name('login');

        Route::post('/verify-otp', [CustomerAuthController::class, 'verifyOtp'])
            ->name('verify-otp');

        Route::post('/verify-email', [CustomerAuthController::class, 'verifyEmail'])
            ->name('verify-email');

        Route::post('/resend-verification', [CustomerAuthController::class, 'resendVerification'])
            ->name('resend-verification');

        Route::post('/forgot-password', [CustomerAuthController::class, 'forgotPassword'])
            ->name('forgot-password');

        Route::post('/reset-password', [CustomerAuthController::class, 'resetPassword'])
            ->name('reset-password');

        Route::middleware([
                'auth:sanctum',
                'active',
                'trusted:customer',
                //'otp',
                'panel:customer',
                'role:customer',
            ])->group(function (): void {

                Route::post('/logout', [CustomerAuthController::class, 'logout'])
                    ->name('logout');
            });
    });
