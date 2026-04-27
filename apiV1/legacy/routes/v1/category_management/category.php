<?php

use App\Http\Controllers\CategoryManagement\CategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('category')->middleware('auth:api')->group(function () {
    
    // Create, Read, Update, Delete (CRUD)
    Route::middleware('permission:view categories')->get('/', [CategoryController::class, 'index']);
    Route::middleware('permission:view categories')->get('/{id}', [CategoryController::class, 'show']);
    Route::middleware('permission:create categories')->post('/create', [CategoryController::class, 'store']);
    Route::middleware('permission:update categories')->put('/update/{id}', [CategoryController::class, 'update']);
    Route::middleware('permission:delete categories')->delete('/delete/{id}', [CategoryController::class, 'destroy']);
    
    // Other Routes
    Route::middleware('permission:view active categories')->get('/active', [CategoryController::class, 'activeCategories']);
    Route::middleware('permission:view parent categories')->get('/parents', [CategoryController::class, 'parentCategories']);
    Route::middleware('permission:view categories with subcategories')->get('/with-subcategories', [CategoryController::class, 'categoriesWithSubcategories']);
    Route::middleware('permission:search categories')->get('/search', [CategoryController::class, 'search']);
    
    // Bulk Operations
    Route::middleware('permission:create categories')->post('/bulk-create', [CategoryController::class, 'bulkCreate']);
    Route::middleware('permission:update categories')->put('/bulk-update', [CategoryController::class, 'bulkUpdate']);
    Route::middleware('permission:delete categories')->delete('/bulk-delete', [CategoryController::class, 'bulkDelete']);
    
    // Reorder & Soft Delete
    Route::middleware('permission:update categories')->post('/reorder', [CategoryController::class, 'reorder']);
    Route::middleware('permission:delete categories')->delete('/soft-delete/{id}', [CategoryController::class, 'softDelete']);
    Route::middleware('permission:update categories')->post('/restore/{id}', [CategoryController::class, 'restore']);
    
    // Category Count
    Route::middleware('permission:view categories')->get('/count', [CategoryController::class, 'count']);
});