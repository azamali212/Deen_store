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
        // P9-4 — rename. C6 locked store_name at approval; this is the way
        // to change it without closing the shop and starting over.
        Route::post('name', [SellerProfileController::class, 'requestNameChange'])->name('name.request');
        Route::delete('name', [SellerProfileController::class, 'withdrawNameChange'])->name('name.withdraw');

        // P8-5 / P8-6 — the seller's exit and the way back. Both sit inside
        // the normal seller group: after closing, the OWNER keeps their
        // seller role precisely so this route stays reachable (C39).
        Route::post('close', [SellerProfileController::class, 'close'])->name('close');
        Route::post('reopen', [SellerProfileController::class, 'requestReopen'])->name('reopen');

        // C35 — also a billable AI call made before the file is stored.
        Route::post('bank/statement', [SellerProfileController::class, 'uploadBankProof'])
            ->middleware('throttle:seller-bank-proof')
            ->name('bank.statement');
    });
