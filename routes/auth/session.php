<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('v1/sessions')
    ->name('sessions.')
    ->group(function (): void {

        Route::get('/', [SessionController::class, 'index'])
            ->name('index');
    });