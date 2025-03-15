<?php

use App\Events\EmailStatusUpdated;
use App\Events\TestNotification;
use App\Models\Email;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Broadcast::routes();

Route::get('/test-email-event/{emailId}', function ($emailId) {
    $email = Email::find($emailId);

    if ($email) {
        // Trigger the event
        event(new EmailStatusUpdated($emailId));

        return response()->json(['message' => 'Event triggered']);
    }

    return response()->json(['error' => 'Email not found.'], 404);
});

Route::prefix('v1')->group(function () {
    require base_path('routes/v1/auth/auth.php');
    require base_path('routes/v1/user/user.php');
    require base_path('routes/v1/permission_settings/role.php');
    require base_path('routes/v1/permission_settings/permission.php');
    require base_path('routes/v1/product_management/product.php');
    require base_path('routes/v1/user_activity/user_activity.php');
    require base_path('routes/v1/category_management/category.php');
    require base_path('routes/v1/email/email.php');
    require base_path('routes/v1/cart/cart.php');
    require base_path('routes/v1/gift/gift.php');
    require base_path('routes/v1/order/order.php');
});
