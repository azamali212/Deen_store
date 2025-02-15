<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProductManagement\Product\ProductController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    Route::get('/products',[ProductController::class,'index'])->middleware('permission:product-index');
    Route::post('/products',[ProductController::class,'store'])->middleware('permission:product-create');
});