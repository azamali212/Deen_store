<?php

use App\Http\Controllers\Permission_Settings\PermissionController;
use App\Http\Controllers\Permission_Settings\RoleController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:api')->group(function () {
    Route::get('/permission', [PermissionController::class, 'index'])->middleware('permission:permission-index'); // Get all permissions (paginated)
    Route::get('/permission/{id}', [PermissionController::class, 'show'])->middleware('permission:permission-show'); 
    Route::post('/permission', [PermissionController::class, 'store'])->middleware('permission:permission-create');
    Route::put('/permission/{id}', [PermissionController::class, 'update'])->middleware('permission:permission-edit');
    Route::delete('/permission/{id}', [PermissionController::class, 'destroy'])->middleware('permission:permission-delete');
    Route::get('/permission/details/{permissionId}', [PermissionController::class, 'getPermissionDetails']); // Get detailed permission information
});