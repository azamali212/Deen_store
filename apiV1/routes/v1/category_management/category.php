<?php

use App\Http\Controllers\CategoryManagement\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {

    Route::get('/category', [CategoryController::class, 'index']);
    Route::get('/category/{id}', [CategoryController::class, 'show']);
    Route::post('/category/create', [CategoryController::class, 'store']);
    Route::put('/category/update/{id}', [CategoryController::class, 'update']);
    Route::delete('/category/delete/{id}', [CategoryController::class, 'destroy']);

    Route::get('/active', [CategoryController::class, 'activeCategories']);
    Route::get('/parents', [CategoryController::class, 'parentCategories']);
    Route::get('/with-subcategories', [CategoryController::class, 'categoriesWithSubcategories']);
    Route::get('/search', [CategoryController::class, 'search']);

    Route::post('/bulk-create', [CategoryController::class, 'bulkCreate']);
    Route::put('/bulk-update', [CategoryController::class, 'bulkUpdate']);
    Route::delete('/bulk-delete', [CategoryController::class, 'bulkDelete']);

    Route::post('/reorder', [CategoryController::class, 'reorder']);
    Route::delete('/soft-delete/{id}', [CategoryController::class, 'softDelete']);
    Route::post('/restore/{id}', [CategoryController::class, 'restore']);

    Route::get('/count', [CategoryController::class, 'count']);
});
