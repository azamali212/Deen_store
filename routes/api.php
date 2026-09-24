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
require __DIR__.'/user/profile.php';
require __DIR__.'/user/admin.php';
require __DIR__.'/audit/admin.php';
require __DIR__.'/moderation/admin.php';
require __DIR__.'/seller/application.php';
require __DIR__.'/seller/admin.php';
require __DIR__.'/seller/profile.php';
require __DIR__.'/seller/team.php';
require __DIR__."/seller/renewals.php";
