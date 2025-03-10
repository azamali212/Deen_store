<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Cart\CartController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


Route::middleware('auth:api')->group(function () {
      // Get cart for the authenticated user
      Route::get('cart', [CartController::class, 'getCart']);

      // Add product to cart
      Route::post('cart/add', [CartController::class, 'addToCart']);
  
      // Update cart item
      Route::put('cart/{cart_item_id}', [CartController::class, 'updateCart']);
  
      // Delete cart item
      Route::delete('cart/{cart_item_id}', [CartController::class, 'deleteCartItem']);
  
      // Clear the cart
      Route::delete('cart/clear/{user_id}', [CartController::class, 'clearCart']);
  
      // Apply discount
      Route::post('cart/apply-discount', [CartController::class, 'applyDiscount']);
  
      // Calculate tax
      Route::post('cart/calculate-tax', [CartController::class, 'calculateTax']);
  
      // Merge guest cart to user
      Route::post('cart/merge-guest-cart', [CartController::class, 'mergeGuestCartToUser']);
  
      // Move item to wishlist
      Route::post('cart/{cart_item_id}/move-to-wishlist', [CartController::class, 'moveToWishlist']);
  
      // Recover abandoned carts
      Route::post('cart/recover-abandoned/{userId}', [CartController::class, 'recoverAbandonedCarts']);
  
      // Get personalized recommendations
      Route::get('cart/personalized-recommendations', [CartController::class, 'getPersonalizedRecommendations']);
  
      // Validate stock before checkout
      Route::post('cart/validate-stock', [CartController::class, 'validateStockBeforeCheckout']);
  
      // Apply currency conversion
      Route::post('cart/apply-currency-conversion', [CartController::class, 'applyCurrencyConversion']);
  
      // Auto apply best discount
      Route::post('cart/auto-apply-best-discount', [CartController::class, 'autoApplyBestDiscount']);
  
      // Estimate delivery date
      Route::get('cart/estimate-delivery-date', [CartController::class, 'estimateDeliveryDate']);
  
      // Handle subscription items
      Route::post('cart/handle-subscription', [CartController::class, 'handleSubscriptionItems']);
  
      // Save cart state
      Route::post('cart/save-state', [CartController::class, 'saveCartState']);
  
      // Reorder past cart
      Route::post('cart/reorder-past', [CartController::class, 'reorderPastCart']);
  
      // Handle split payments
      Route::post('cart/handle-split-payments', [CartController::class, 'handleSplitPayments']);
});