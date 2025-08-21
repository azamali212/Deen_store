<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::post('register', [AuthController::class, 'register']);
Route::get('email/verify/{token}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/user-login', [AuthController::class, 'login']);
Route::post('/resend-verification', [AuthController::class, 'resendVerificationEmail']);


Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/user-logout', [AuthController::class, 'logout']);
    Route::post('/switch-role', [AuthController::class, 'switchRole']);

    Route::get('/me', function (Request $request) {
        $user = $request->user();

        // Get active role from cookie or default to first role
        $activeRole = $request->cookie('user_role', $user->getRoleNames()->first());

        // Filter permissions based on active role
        $permissions = $user->getPermissionsViaRoles()
            ->filter(function ($permission) use ($activeRole) {
                return $permission->roles->contains('name', $activeRole);
            })
            ->pluck('name');

        return response()->json([
            'user' => $user,
            'active_role' => $activeRole,
            'all_roles' => $user->getRoleNames(),
            'permissions' => $permissions,
            'all_permissions' => $user->getAllPermissions()->pluck('name')
        ]);
    });
});
