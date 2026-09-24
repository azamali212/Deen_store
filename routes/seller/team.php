<?php

declare(strict_types=1);

use App\Http\Controllers\Seller\SellerInvitationController;
use App\Http\Controllers\Seller\SellerTeamController;
use Illuminate\Support\Facades\Route;

// Inside a store: any active member may look, only the owner may change
// (P7-3). No {store} in the URL — it comes from the caller's membership.
Route::middleware([
    'auth:sanctum',
    'active',
    'role:seller|seller_manager|seller_staff',
    'panel:seller',
])
    ->prefix('v1/seller/team')
    ->name('seller.team.')
    ->group(function (): void {

        Route::get('/', [SellerTeamController::class, 'index'])->name('index');
        Route::post('/', [SellerTeamController::class, 'store'])->name('store');

        Route::patch('{member}', [SellerTeamController::class, 'update'])
            ->whereNumber('member')
            ->name('update');

        Route::delete('{member}', [SellerTeamController::class, 'destroy'])
            ->whereNumber('member')
            ->name('destroy');
    });

// C21 — NO seller role here. An invitee is still just a customer at this
// point; accepting is what grants them the role.
Route::middleware([
    'auth:sanctum',
    'active',
])
    ->prefix('v1/seller/invitations')
    ->name('seller.invitations.')
    ->group(function (): void {

        Route::get('/', [SellerInvitationController::class, 'index'])->name('index');

        Route::post('{invitation}/accept', [SellerInvitationController::class, 'accept'])
            ->whereNumber('invitation')
            ->name('accept');

        Route::post('{invitation}/decline', [SellerInvitationController::class, 'decline'])
            ->whereNumber('invitation')
            ->name('decline');
    });
