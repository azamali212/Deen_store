<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

require __DIR__.'/auth/admin.php';
require __DIR__.'/auth/seller.php';
require __DIR__.'/auth/customer.php';
require __DIR__.'/auth/session.php';