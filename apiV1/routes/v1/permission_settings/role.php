<?php


use App\Http\Controllers\Permission_Settings\RoleController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:api')->group(function () {
    Route::get('/role',[RoleController::class, 'index'])->middleware('permission:role-index');
    Route::post('/role/create',[RoleController::class, 'store'])->middleware('permission:role-create');
    Route::post('/role/{id}',[RoleController::class, 'show'])->middleware('permission:role-show');
    Route::put('/role/{id}',[RoleController::class, 'update'])->middleware('permission:role-edit');
    Route::delete('/role/{id}',[RoleController::class, 'destroy'])->middleware('permission:role-delete');
    Route::post('/roles/{id}/permissions', [RoleController::class, 'attachPermissions']);
    Route::post('/roles/{id}/permissions/detach', [RoleController::class, 'detachPermissions']);
    Route::post('roles/{id}/attach-users', [RoleController::class, 'attachUsers']);
    Route::post('/roles/{id}/detach', [RoleController::class, 'detachUsers']);
    Route::get('/roles/{id}/permissions', [RoleController::class, 'getPermissions']);
    Route::get('/roles/{id}/users', [RoleController::class, 'getUsers']);
    Route::delete('/roles/destroy-multiple', [RoleController::class, 'destroyMultiple']);

    //Route::get('/roles/{slug}', [RoleController::class, 'getRoleBySlug']);
    //Route::get('/roles/{slug}/permissions', [RoleController::class, 'getRolePermissionsBySlugAndUser']);
    //Route::get('/roles/{slug}/user', [RoleController::class, 'getRoleBySlugAndUser']);
    //Route::get('/roles/{slug}/user/{userId}', [RoleController::class, 'getRoleBySlugAndUserId']);
    //Route::get('/roles/{slug}/permissions/user/{userId}', [RoleController::class, 'getRolePermissionsBySlugAndUserId']);
    //Route::get('/roles/{slug}/user/email/{email}', [RoleController::class, 'getRoleBySlugAndUserEmail']);
    //Route::get('/roles/{slug}/permissions/user/email/{email}', [RoleController::class, 'getRolePermissionsBySlugAndUserEmail']);

    //All have otimize Route
    Route::get('/roles/details/{slug}', [RoleController::class, 'getRoleDetails']);
    Route::prefix('role-requests')->group(function () {
        // For super admins
        Route::middleware('role:Super Admin')->group(function () {
            Route::get('/pending', [RoleController::class, 'getPendingRequests']);
            Route::get('/stats', [RoleController::class, 'getRequestStats']);
            Route::get('/notification-stats', [RoleController::class, 'getNotificationStats']);
            Route::post('/{id}/approve', [RoleController::class, 'approveRequest']);
            Route::post('/{id}/reject', [RoleController::class, 'rejectRequest']);
            Route::post('/{id}/resend-notifications', [RoleController::class, 'resendNotifications']);
            Route::post('/escalate-overdue', [RoleController::class, 'escalateOverdueRequests']);
            Route::get('/overdue', [RoleController::class, 'getOverdueRequests']); // Add this method to controller
        });
        
        // For regular users
        Route::get('/my-requests', [RoleController::class, 'getMyPendingRequests']);
        Route::get('/my-requests/{id}', [RoleController::class, 'getMyRequestDetails']); // Add this method
    });
});