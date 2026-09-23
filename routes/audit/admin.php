<?php

declare(strict_types=1);

use App\Http\Controllers\Audit\AuditSummaryController;
use Illuminate\Support\Facades\Route;

// Admin-only, mirrors routes/user/admin.php's gate exactly.
Route::prefix('v1/admin/audit')
    ->name('admin.audit.')
    ->middleware([
        'auth:sanctum',
        'active',
        'role:super_admin|platform_admin',
    ])
    ->group(function (): void {

        Route::get('users/{user}/summary', [AuditSummaryController::class, 'forUser'])
            ->name('users.summary');
    });
