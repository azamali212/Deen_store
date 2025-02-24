<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProductManagement\AIFeatureProduct\UserActivityController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
   // Store a new user activity
   Route::post('/user_activity', [UserActivityController::class, 'store']);

   // Get all user activities
   Route::get('/user_activity', [UserActivityController::class, 'index']);

   // Get user activities by user ID
   Route::get('/user_activity/{userId}', [UserActivityController::class, 'getByUser']);

   // Get user activities by product ID
   Route::get('/user_activity/product/{productId}', [UserActivityController::class, 'getByProduct']);

   // Get most popular products based on user activities
   Route::get('/user_activity/popular-products/{limit}', [UserActivityController::class, 'getMostPopularProducts']);

   // Get a user's activity count
   Route::get('/user_activity/user/{userId}/total-actions', [UserActivityController::class, 'getTotalActionsByUser']);

   // Get user activities within a date range
   Route::get('/user_activity/user/{userId}/date-range/{startDate}/{endDate}', [UserActivityController::class, 'getByUserAndDateRange']);

   // Get product activity count within a date range
   Route::get('/user_activity/product/{productId}/activity-count/{startDate}/{endDate}', [UserActivityController::class, 'getProductActivityCountByDateRange']);

   // Get recommended products based on user actions
   Route::get('/user_activity/user/{userId}/recommended-products', [UserActivityController::class, 'getRecommendedProductsForUser']);

   // Get user activity trends for a specific time frame
   Route::get('/user_activity/user/{userId}/activity-trends/{timeFrame}', [UserActivityController::class, 'getUserActivityTrends']);

   // Get filtered user activity based on device type and IP
   Route::get('/user_activity/user/{userId}/filtered-activity', [UserActivityController::class, 'getFilteredUserActivity']);
});
