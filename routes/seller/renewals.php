<?php

declare(strict_types=1);

use App\Http\Controllers\Seller\AdminSellerRenewalController;
use App\Http\Controllers\Seller\SellerDocumentRenewalController;
use Illuminate\Support\Facades\Route;

// Phase 8a — a live seller replaces an expiring KYC document.
// Same role list as the rest of the seller panel; only the OWNER gets past
// SellerProfileService::guardKycDocuments().
Route::middleware([
    'auth:sanctum',
    'active',
    'role:seller|seller_manager|seller_staff',
    'panel:seller',
])
    ->prefix('v1/seller/documents')
    ->name('seller.documents.')
    ->group(function (): void {

        Route::get('/', [SellerDocumentRenewalController::class, 'index'])->name('index');

        // C35 — each upload is a billable AI call made BEFORE the file is
        // stored, so even a rejected one costs money. The throttle sits
        // ahead of the controller: a blocked request never reaches the AI.
        Route::post('/', [SellerDocumentRenewalController::class, 'store'])
            ->middleware('throttle:seller-documents')
            ->name('store');
    });

Route::middleware([
    'auth:sanctum',
    'active',
    'role:super_admin|platform_admin',
])
    ->prefix('v1/admin/seller-renewals')
    ->name('admin.seller-renewals.')
    ->group(function (): void {

        Route::get('/', [AdminSellerRenewalController::class, 'index'])->name('index');

        Route::get('{renewal}/file', [AdminSellerRenewalController::class, 'download'])
            ->whereNumber('renewal')
            ->name('download');

        Route::post('{renewal}/review', [AdminSellerRenewalController::class, 'review'])
            ->whereNumber('renewal')
            ->name('review');
    });
