<?php

declare(strict_types=1);

use App\Http\Controllers\User\AddressController;
use App\Http\Controllers\User\AvatarController;
use App\Http\Controllers\User\DataExportController;
use App\Http\Controllers\User\MeController;
use App\Http\Controllers\User\PhoneVerificationController;
use App\Http\Controllers\User\PreferenceController;
use App\Http\Controllers\User\PrivacyController;
use App\Http\Controllers\User\ProfileController;
use Illuminate\Support\Facades\Route;

// Self-service: any authenticated + active user manages their OWN data here.
// No {user} route param anywhere — everything resolves off $request->user(),
// so there is no IDOR surface to worry about on these routes.
Route::middleware([
    'auth:sanctum',
    'active',
])
    ->prefix('v1')
    ->group(function (): void {

        // A1 — who am I and what may I do. Re-readable, so a client can
        // refresh after a role change without logging out again.
        Route::get('me', [MeController::class, 'show'])->name('me');

        Route::prefix('profile')
            ->name('profile.')
            ->group(function (): void {

                Route::get('/', [ProfileController::class, 'show'])->name('show');
                Route::put('/', [ProfileController::class, 'update'])->name('update');

                Route::post('avatar', [AvatarController::class, 'store'])->name('avatar.store');
                Route::delete('avatar', [AvatarController::class, 'destroy'])->name('avatar.destroy');
            });

        Route::prefix('addresses')
            ->name('addresses.')
            ->group(function (): void {

                Route::get('/', [AddressController::class, 'index'])->name('index');
                Route::post('/', [AddressController::class, 'store'])->name('store');
                Route::put('{address}', [AddressController::class, 'update'])->name('update');
                Route::delete('{address}', [AddressController::class, 'destroy'])->name('destroy');
                Route::post('{address}/default', [AddressController::class, 'setDefault'])->name('set-default');
            });

        Route::prefix('preferences')
            ->name('preferences.')
            ->group(function (): void {

                Route::get('/', [PreferenceController::class, 'show'])->name('show');
                Route::put('/', [PreferenceController::class, 'update'])->name('update');
            });

        Route::prefix('phone')
            ->name('phone.')
            ->group(function (): void {

                Route::post('send-code', [PhoneVerificationController::class, 'sendCode'])->name('send-code');
                Route::post('verify', [PhoneVerificationController::class, 'verify'])->name('verify');
            });

        Route::prefix('privacy')
            ->name('privacy.')
            ->group(function (): void {

                // Art. 15 — give me a copy of what you hold.
                Route::get('data-export', [DataExportController::class, 'export'])->name('data-export');

                // A5 / Art. 17 — erase it. A request with a grace period, not
                // an immediate delete; see AccountErasureService.
                Route::get('erasure', [PrivacyController::class, 'erasureStatus'])->name('erasure.status');
                Route::post('erasure', [PrivacyController::class, 'requestErasure'])->name('erasure.request');
                Route::delete('erasure', [PrivacyController::class, 'cancelErasure'])->name('erasure.cancel');

                // A5 / Art. 7 — the consent ledger.
                Route::get('consents', [PrivacyController::class, 'consents'])->name('consents.index');
                Route::post('consents', [PrivacyController::class, 'grantConsent'])->name('consents.store');
                Route::delete('consents/{type}', [PrivacyController::class, 'withdrawConsent'])->name('consents.withdraw');
            });
    });
