<?php

use App\Events\TestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::prefix('v1')->group(function () {
    require base_path('routes/v1/auth/auth.php');
    require base_path('routes/v1/user/user.php');
    require base_path('routes/v1/permission_settings/role.php');
    require base_path('routes/v1/permission_settings/permission.php');
    require base_path('routes/v1/product_management/product.php');
    require base_path('routes/v1/user_activity/user_activity.php');
    require base_path('routes/v1/category_management/category.php');
});
