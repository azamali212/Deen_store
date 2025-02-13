<?php


use App\Http\Controllers\Permission_Settings\RoleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    Route::get('/role',[RoleController::class, 'index'])->middleware('permission:role-index');
    Route::post('/role/create',[RoleController::class, 'store'])->middleware('permission:role-create');
    Route::post('/role/{id}',[RoleController::class, 'show'])->middleware('permission:role-show');
    Route::put('/role/{id}',[RoleController::class, 'update'])->middleware('permission:role-edit');
    Route::delete('/role/{id}',[RoleController::class, 'destroy'])->middleware('permission:role-delete');
    Route::post('/roles/{id}/permissions', [RoleController::class, 'attachPermissions']);
    Route::post('/roles/{id}/permissions/detach', [RoleController::class, 'detachPermissions']);
    Route::post('roles/{id}/attach-users', [RoleController::class, 'attachUsers']);
});