<?php

use App\Http\Controllers\AI\AIController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProductManagement\Product\ProductController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {

    Route::get('/products',[ProductController::class,'index'])->middleware('permission:product-index');
    Route::get('/products/{id}',[ProductController::class,'show'])->middleware('permission:product-show');
    Route::post('/products',[ProductController::class,'store'])->middleware('permission:product-create');
    Route::put('/products/{id}',[ProductController::class,'update'])->middleware('permission:product-edit');
    Route::delete('/products/{id}',[ProductController::class,'destroy'])->middleware('permission:product-delete');

    //Filter Product
    Route::post('/products/filter', [ProductController::class, 'filter']);
    Route::get('/products/sort-and-paginate', [ProductController::class, 'sortAndPaginate']);

    //Recommended Product 
    //Route::get('/products/recommend/{userId}', [ProductController::class, 'recommendCategory']);
    Route::post('/product/{productId}/view', [ProductController::class, 'trackProductView']);
    Route::get('/products/showCategories',[ProductController::class,'showCategories']);
    Route::get('/products/recommend/{userId}', [ProductController::class, 'recommendProducts']);
    //Route::post('/ask-ai', [AIController::class, 'askAI']);
    //SearchAble
    ROute::post('/products/search',[ProductController::class,'search']);

});