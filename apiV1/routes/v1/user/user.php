<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {

    //Searching User
    Route::get('/users/search', [UserController::class, 'searchUsers']);
    Route::get('/user/recycleBinUsers',[UserController::class, 'recycleBinUsers'])->name('recycleBinUsers');

    //Main Crud 
    Route::get('/users',[UserController::class, 'getAllUsers'])->middleware('permission:user-index');
    Route::get('/user/{id}',[UserController::class, 'show'])->middleware('permission:user-show');
    Route::post('/user',[UserController::class, 'createUser'])->middleware('permission:user-create');
    Route::delete('/user/{id}',[UserController::class, 'deleteUser'])->middleware('permission:user-delete');
    Route::put('/user/{id}',[UserController::class, 'updateUser'])->middleware('permission:user-edit');
    Route::get('/user/restore/{id}',[UserController::class, 'restoreUser']);//->middleware('permission:user-restore');
    Route::delete('/user/{id}/permanentDelete',[UserController::class, 'forceDeleteUser']);//->middleware('permission:user-permissions')

    //Activit Log
    Route::patch('/user/{id}/userRole',[UserController::class, 'changeUserRole']);//->middleware('permission:user-roles');
    Route::post('/user/log', [UserController::class, 'logUserAction']);//->middleware('permission:user-log');
    Route::post('/users/{id}/active', [UserController::class, 'userActive']);
    Route::post('/users/{id}/inactivate', [UserController::class, 'userInActive']);
    Route::delete('/users/batch-delete', [UserController::class, 'batchDeleteUsers']);
    Route::delete('/users/batch-restore', [UserController::class, 'batchRestoreUsers']);
    Route::delete('/users/batch-permanent-delete', [UserController::class, 'betchForceDeleteUsers']);

    //Advanced Queries
    Route::get('/users/inactive', [UserController::class, 'getInactiveUsers']);  // GET /api/users/inactive
    Route::post('/users/advanced-criteria', [UserController::class, 'getUsersByAdvancedCriteria']);  // POST /api/users/advanced-criteria
    Route::post('/users/permission', [UserController::class, 'getUsersWithPermission']);  // POST /api/users/permission
    Route::post('/users/bulk-update-status', [UserController::class, 'bulkUpdateUserStatus']);  // POST /api/users/bulk-update-status
    Route::post('/users/cache-query', [UserController::class, 'cacheQueryResult']);  // POST /api/users/cache-query
    Route::post('/users/assign-permissions', [UserController::class, 'assignPermissionsToUser']);  // POST 
});