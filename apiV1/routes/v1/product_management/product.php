<?php

use App\Http\Controllers\AI\AIController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProductManagement\AIFeatureProduct\AIProductController;
use App\Http\Controllers\ProductManagement\AIFeatureProduct\MoreAIProduct\CartAbandonmentController;
use App\Http\Controllers\ProductManagement\AIFeatureProduct\MoreAIProduct\CollaborativeFilteringController;
use App\Http\Controllers\ProductManagement\AIFeatureProduct\MoreAIProduct\GeolocationRecommendationController;
use App\Http\Controllers\ProductManagement\Product\ProductController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
    
    //Product Simple Crud
    Route::get('/products',[ProductController::class,'index'])->middleware('permission:product-index');
    Route::get('/products/{id}',[ProductController::class,'show'])->middleware('permission:product-show');
    Route::post('/products',[ProductController::class,'store'])->middleware('permission:product-create');
    Route::put('/products/{id}',[ProductController::class,'update'])->middleware('permission:product-edit');
    Route::delete('/products/{id}',[ProductController::class,'destroy'])->middleware('permission:product-delete');

    //Filter Product
    Route::post('/products/filter', [ProductController::class, 'filter']);
    Route::get('/products/sort-and-paginate', [ProductController::class, 'sortAndPaginate']);

    //AI Product 
    //Route::get('/products/showCategories',[ProductController::class,'showCategories']);
    //Route::post('/ask-ai', [AIController::class, 'askAI']);
    //SearchAble
    //Route::post('/products/search',[ProductController::class,'search']);
    //Route::get('/products/recommend/{userId}', [ProductController::class, 'recommendCategory']);


    Route::get('/recommend-products/{userId}', [AIProductController::class, 'getRecommendedProducts']);
    Route::get('/recommend-category/{userId}', [AIProductController::class, 'getRecommendedCategory']);
    Route::get('/trending-products', [AIProductController::class, 'getTrendingProducts']);
    Route::get('/track-category-view/{productId}', [AIProductController::class, 'trackCategoryView']);
    Route::get('/track-product-view/{productId}', [AIProductController::class, 'trackProductView']);
    //Test Route
    //Route::post('/product/recommendations/{productId}', [AIProductController::class, 'getProductRecommendations']);

    //More Features
    Route::get('/cart-abandonment/{userId}', [CartAbandonmentController::class, 'trackCart']);
    Route::get('/track-collaborative/{userId}', [CollaborativeFilteringController::class, 'trackCollaborative']);

    //Product Recommendation Using Geo Location // Route to get product recommendations based on user's location
    Route::get('/recommendations/{userId}', [GeolocationRecommendationController::class, 'getRecommendations']);

    // Route to save user's location
    Route::post('/save-location/{userId}', [GeolocationRecommendationController::class, 'saveLocation']);

    // Route to get product recommendations based on a specific location
    Route::get('/recommendations-by-location/{location}', [GeolocationRecommendationController::class, 'getRecommendationsByLocation']);

    // Route to get user location data
    Route::get('/user-location/{userId}', [GeolocationRecommendationController::class, 'getUserLocation']);

    // Route to get category ID based on a location
    Route::get('/category-id-by-location/{location}', [GeolocationRecommendationController::class, 'getCategoryIdByLocation']);
    
});