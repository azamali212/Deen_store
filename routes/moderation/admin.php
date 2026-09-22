<?php

declare(strict_types=1);

use App\Http\Controllers\Moderation\ModerationFlagController;
use Illuminate\Support\Facades\Route;

// Admin-only, mirrors routes/audit/admin.php's gate exactly.
Route::prefix('v1/admin/moderation')
    ->name('admin.moderation.')
    ->middleware([
        'auth:sanctum',
        'active',
        'role:super_admin|admin',
    ])
    ->group(function (): void {

        Route::get('flags', [ModerationFlagController::class, 'index'])
            ->name('flags.index');

        Route::patch('flags/{flag}/resolve', [ModerationFlagController::class, 'resolve'])
            ->name('flags.resolve');
    });
