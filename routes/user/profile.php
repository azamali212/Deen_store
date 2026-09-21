<?php

declare(strict_types=1);

use App\Http\Controllers\User\AddressController;
use App\Http\Controllers\User\AvatarController;
use App\Http\Controllers\User\PreferenceController;
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
    });
