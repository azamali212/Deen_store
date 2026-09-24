<?php

declare(strict_types=1);

use App\Http\Controllers\Seller\AdminSellerApplicationController;
use App\Http\Controllers\Seller\AdminSellerController;
use Illuminate\Support\Facades\Route;

// Admin review of seller applications (BLUEPRINT.txt section 3).
// platform_admin, NOT the non-existent 'admin' role (D10).
Route::middleware([
    'auth:sanctum',
    'active',
    'role:super_admin|platform_admin',
])
    ->prefix('v1/admin/seller-applications')
    ->name('admin.seller-applications.')
    ->group(function (): void {

        Route::get('/', [AdminSellerApplicationController::class, 'index'])->name('index');

        Route::get('{application}', [AdminSellerApplicationController::class, 'show'])
            ->whereNumber('application')
            ->name('show');

        // {type} binds straight to the SellerDocumentType enum — an unknown
        // type is a 404 before the controller even runs.
        Route::get('{application}/documents/{type}', [AdminSellerApplicationController::class, 'downloadDocument'])
            ->whereNumber('application')
            ->name('documents.download');

        Route::post('{application}/review', [AdminSellerApplicationController::class, 'review'])
            ->whereNumber('application')
            ->name('review');
    });

// Phase 6 — LIVE stores (as opposed to applications above).
Route::middleware([
    'auth:sanctum',
    'active',
    'role:super_admin|platform_admin',
])
    ->prefix('v1/admin/sellers')
    ->name('admin.sellers.')
    ->group(function (): void {

        Route::get('/', [AdminSellerController::class, 'index'])->name('index');

        Route::get('{seller}', [AdminSellerController::class, 'show'])
            ->whereNumber('seller')
            ->name('show');

        Route::post('{seller}/suspend', [AdminSellerController::class, 'suspend'])
            ->whereNumber('seller')
            ->name('suspend');

        Route::post('{seller}/reactivate', [AdminSellerController::class, 'reactivate'])
            ->whereNumber('seller')
            ->name('reactivate');

        Route::post('{seller}/name/review', [AdminSellerController::class, 'reviewNameChange'])
            ->whereNumber('seller')
            ->name('name.review');

        Route::post('{seller}/reopen', [AdminSellerController::class, 'reopen'])
            ->whereNumber('seller')
            ->name('reopen');

        Route::get('{seller}/bank/statement', [AdminSellerController::class, 'downloadBankProof'])
            ->whereNumber('seller')
            ->name('bank.statement');

        Route::post('{seller}/bank/review', [AdminSellerController::class, 'reviewBankProof'])
            ->whereNumber('seller')
            ->name('bank.review');
    });
