<?php

declare(strict_types=1);

use App\Http\Controllers\Seller\SellerApplicationController;
use Illuminate\Support\Facades\Route;

// Customer side of seller onboarding (BLUEPRINT.txt section 3).
// No {id} param: a user has at most ONE application, resolved from
// $request->user() — no IDOR surface (C5).
Route::middleware([
    'auth:sanctum',
    'active',
    'role:customer',
])
    ->prefix('v1/seller/application')
    ->name('seller.application.')
    ->group(function (): void {

        Route::post('/', [SellerApplicationController::class, 'store'])->name('store');
        Route::get('/', [SellerApplicationController::class, 'show'])->name('show');
        Route::patch('/', [SellerApplicationController::class, 'update'])->name('update');

        Route::post('documents', [SellerApplicationController::class, 'uploadDocument'])->name('documents.store');

        Route::post('submit', [SellerApplicationController::class, 'submit'])->name('submit');
    });
