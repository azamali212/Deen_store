<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin/auth')
    ->name('admin.auth.')
    ->group(function (): void {

        Route::post('/login', [AdminAuthController::class, 'login'])->name('login');

        Route::post('/verify-otp', [AdminAuthController::class, 'verifyOtp'])
            ->name('verify-otp');

        Route::post('/verify-email', [AdminAuthController::class, 'verifyEmail'])
            ->name('verify-email');

        Route::post('/resend-verification', [AdminAuthController::class, 'resendVerification'])
            ->name('resend-verification');

        Route::post('/forgot-password', [AdminAuthController::class, 'forgotPassword'])
            ->name('forgot-password');

        Route::post('/reset-password', [AdminAuthController::class, 'resetPassword'])
            ->name('reset-password');

        Route::middleware([
            'auth:sanctum',
            'active',
            'trusted:admin',
            // 'otp',
            'panel:admin',
            'role:super_admin',
        ])->group(function (): void {

            Route::post('/register', [AdminAuthController::class, 'register'])
                ->name('register');

            Route::post('/logout', [AdminAuthController::class, 'logout'])
                ->name('logout');

            Route::post('/change-password', [AdminAuthController::class, 'changePassword'])
                ->name('change-password');

            Route::get('/sessions', [AdminAuthController::class, 'sessions'])
                ->name('sessions');

            Route::post('/sessions/logout', [AdminAuthController::class, 'logoutSession'])
                ->name('sessions.logout');

            Route::post('/sessions/logout-others', [AdminAuthController::class, 'logoutOtherSessions'])
                ->name('sessions.logout-others');

            Route::get('/trusted-devices', [AdminAuthController::class, 'trustedDevices'])
                ->name('trusted-devices');

            Route::delete('/trusted-devices', [AdminAuthController::class, 'revokeTrustedDevice'])
                ->name('trusted-devices.revoke');

            Route::post('/unlock-account', [AdminAuthController::class, 'unlockAccount'])
                ->name('unlock-account');
        });
    });