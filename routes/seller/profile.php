<?php

declare(strict_types=1);

use App\Http\Controllers\Seller\SellerProfileController;
use Illuminate\Support\Facades\Route;

// Approved sellers manage their live business (BLUEPRINT.txt section 3).
// panel:seller checks the panel.seller.access permission that comes with
// the seller role — the same check the seller dashboard relies on (C11).
Route::middleware([
    'auth:sanctum',
    'active',
    // P7 — the store is a TEAM now. All three roles carry
    // panel.seller.access; what each may actually DO is decided per action
    // by SellerProfileService, not by the route.
    'role:seller|seller_manager|seller_staff',
    'panel:seller',
])
    ->prefix('v1/seller/profile')
    ->name('seller.profile.')
    ->group(function (): void {

        Route::get('/', [SellerProfileController::class, 'show'])->name('show');
        Route::put('/', [SellerProfileController::class, 'update'])->name('update');

        Route::post('logo', [SellerProfileController::class, 'uploadLogo'])->name('logo.store');
        Route::delete('logo', [SellerProfileController::class, 'deleteLogo'])->name('logo.destroy');

        // P6-2 — proof that the payout account belongs to this seller.
        Route::post('bank/statement', [SellerProfileController::class, 'uploadBankProof'])
            ->name('bank.statement');
    });
