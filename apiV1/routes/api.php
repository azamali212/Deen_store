<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Broadcast::routes();

Route::prefix('v1')->group(function (): void {
    require base_path('routes/v1/auth/auth.php');
    require base_path('routes/v1/user/user.php');
    require base_path('routes/v1/permission_settings/role.php');
    require base_path('routes/v1/permission_settings/permission.php');
    require base_path('routes/v1/product_management/product.php');
    require base_path('routes/v1/user_activity/user_activity.php');
    require base_path('routes/v1/category_management/category.php');
    require base_path('routes/v1/email/email.php');
    //require base_path('routes/v1/notification/notification.php');
    require base_path('routes/v1/cart/cart.php');
    require base_path('routes/v1/gift/gift.php');
    require base_path('routes/v1/order/order.php');
    require base_path('routes/v1/inventory/inventory.php');
    require base_path('routes/v1/supplier/supplier.php');
    require base_path('routes/v1/stripPayment/strip.php');
    require base_path('routes/v1/customer/customer.php');
});
