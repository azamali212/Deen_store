<?php

declare(strict_types=1);

use App\Http\Controllers\User\UserManagementController;
use Illuminate\Support\Facades\Route;

// Admin-only: acts on ANY user by {user} id. 'role:super_admin|admin' matches
// AuthPolicy::manageUsers()'s own rule (hasAnyRole([super_admin, admin])) —
// kept consistent with how routes/auth/admin.php already gates admin routes,
// rather than introducing a separate authorization style for this domain.
Route::prefix('v1/admin/users')
    ->name('admin.users.')
    ->middleware([
        'auth:sanctum',
        'active',
        'role:super_admin|admin',
    ])
    ->group(function (): void {

        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('{user}', [UserManagementController::class, 'show'])->name('show');
        Route::put('{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        Route::post('{user}/restore', [UserManagementController::class, 'restore'])->name('restore');
        Route::post('{user}/suspend', [UserManagementController::class, 'suspend'])->name('suspend');
        Route::post('{user}/activate', [UserManagementController::class, 'activate'])->name('activate');
    });
