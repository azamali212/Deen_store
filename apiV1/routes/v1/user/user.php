<?php

use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {

    //Searching User
    Route::get('/users/search', [UserController::class, 'searchUsers']);
    Route::get('/user/recycleBinUsers', [UserController::class, 'recycleBinUsers'])->name('recycleBinUsers');

    //Main Crud 
    Route::get('/users', [UserController::class, 'getAllUsers'])->middleware('permission:user-index');
    Route::get('/user/{id}', [UserController::class, 'show'])->middleware('permission:user-show');
    Route::post('/user', [UserController::class, 'createUser'])->middleware('permission:user-create');
    Route::delete('/user/{id}', [UserController::class, 'deleteUser'])->middleware('permission:user-delete');
    Route::put('/user/{id}', [UserController::class, 'updateUser'])->middleware('permission:user-edit');
    Route::get('/user/restore/{id}', [UserController::class, 'restoreUser']);
    Route::delete('/user/{id}/permanentDelete', [UserController::class, 'forceDeleteUser'])->middleware('permission:user-permissions');
    Route::post('/users/recycle-bin/bulk-delete', [UserController::class, 'bulkDeleteFromRecycleBin']);
    Route::post('/users/recycle-bin/restore-all', [UserController::class, 'restoreAllFromRecycleBin']);

    //Activit Log //->middleware('permission:user-roles');
    Route::post('/user/log', [UserController::class, 'logUserAction'])->middleware('permission:user-log');
    Route::post('/users/{id}/active', [UserController::class, 'userActive'])->middleware('permission:user-active');
   // Route::post('/users/{id}/inactivate', [UserController::class, 'userInActive'])->middleware('permission:user-inactive');
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

    //User Assgin Role and Remove Role and Change Role
    Route::post('/users/{user}/roles', [UserController::class, 'assignRoles'])
        ->where('user', '[0-9]+'); // Ensure the parameter is numeric
    Route::delete('/users/{userId}/roles', [UserController::class, 'removeRoles']);
    Route::patch('/user/{id}/userRole', [UserController::class, 'changeUserRole']);

    Route::post('/users/{userId}/deactivate', [UserController::class, 'deactivateUser']);
    Route::post('/users/{userId}/activate', [UserController::class, 'activateUser']);

    Route::post('/users/{userId}/revoke-permissions', [UserController::class, 'revokePermissions']);
    Route::put('/users/{userId}/sync-permissions', [UserController::class, 'syncPermissions']);
});


//Add Email verification route for the admin verified they email and also more 