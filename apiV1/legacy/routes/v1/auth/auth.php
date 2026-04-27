<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// --------------------
// PUBLIC AUTH ROUTES
// --------------------

Route::post('register', [AuthController::class, 'register']);
Route::get('email/verify/{token}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/user-login', [AuthController::class, 'login']);
Route::post('/resend-verification', [AuthController::class, 'resendVerificationEmail']);

Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);


// --------------------
// PROTECTED ROUTES
// using MultiGuardAuthenticate
// Supports *ALL* guards automatically
// --------------------

Route::middleware(['multi-auth', 'verified'])->group(function () {

    Route::post('/user-logout', [AuthController::class, 'logout']);
    Route::post('/switch-role', [AuthController::class, 'switchRole']);
    Route::get('/current-profile', [AuthController::class, 'getProfile']);

    

    Route::get('/me', function (Request $request) {

        $user = $request->user();

        // Active role from cookie (optional)
        $activeRole = $request->cookie('user_role', $user->getRoleNames()->first());

        $permissions = $user->getPermissionsViaRoles()
            ->filter(fn($permission) => $permission->roles->contains('name', $activeRole))
            ->pluck('name');

        return response()->json([
            'user'            => $user,
            'active_role'     => $activeRole,
            'all_roles'       => $user->getRoleNames(),
            'permissions'     => $permissions,
            'all_permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    });

});
