<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Cart\CartController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;



Route::middleware(['auth:api', 'cart.security'])->group(function () {
      Route::prefix('cart')->group(function () {
            // Get cart items
            Route::get('/', [CartController::class, 'getCart'])
                  ->middleware(['permission:view cart']);

            // Add to cart with validation middleware
            Route::post('/add', [CartController::class, 'addToCart'])
                  ->middleware(['permission:add to cart']);

            // Update cart item
            Route::put('/{cart_item_id}', [CartController::class, 'updateCart'])
                  ->middleware(['permission:update cart']);

            // Remove item from cart
            Route::delete('/{cart_item_id}', [CartController::class, 'deleteCartItem'])
                  ->middleware(['permission:delete cart item']);

            // Clear cart
            Route::delete('/clear', [CartController::class, 'clearCart'])
                  ->middleware(['permission:clear cart']);

            // Apply discount with anti-fraud check
            Route::post('/apply-discount', [CartController::class, 'applyDiscount'])
                  ->middleware(['permission:apply discount', 'anti-fraud']);

            // Merge guest cart
            Route::post('/merge-guest-cart', [CartController::class, 'mergeGuestCartToUser'])
                  ->middleware(['permission:merge guest cart']);

            // Move to wishlist
            Route::post('/{cart_item_id}/move-to-wishlist', [CartController::class, 'moveToWishlist'])
                  ->middleware(['permission:move to wishlist']);

            // Recover abandoned cart
            Route::post('/recover-abandoned/{user_id}', [CartController::class, 'recoverAbandonedCarts'])
                  ->middleware(['permission:recover abandoned carts']);

            // Get AI-based recommendations
            Route::get('/recommendations', [CartController::class, 'getPersonalizedRecommendations'])
                  ->middleware(['permission:get personalized recommendations']);

            // Validate stock availability
            Route::post('/validate-stock', [CartController::class, 'validateStockBeforeCheckout'])
                  ->middleware(['permission:validate stock']);

            // Multi-currency conversion
            Route::post('/currency-conversion', [CartController::class, 'applyCurrencyConversion'])
                  ->middleware(['permission:apply currency conversion']);

            // Auto apply best available discount
            Route::post('/auto-best-discount', [CartController::class, 'autoApplyBestDiscount'])
                  ->middleware(['permission:auto apply best discount']);

            // Estimate delivery date dynamically
            Route::get('/estimate-delivery', [CartController::class, 'estimateDeliveryDate'])
                  ->middleware(['permission:estimate delivery date']);

            // Handle subscription-based purchases
            Route::post('/handle-subscription', [CartController::class, 'handleSubscriptionItems'])
                  ->middleware(['permission:handle subscription']);

            // Save cart state for later
            Route::post('/save-state', [CartController::class, 'saveCartState'])
                  ->middleware(['permission:save cart state']);

            // Reorder past orders with smart recommendations
            Route::post('/reorder-past', [CartController::class, 'reorderPastCart'])
                  ->middleware(['permission:reorder past cart']);

            // Support split payments
            Route::post('/split-payments', [CartController::class, 'handleSplitPayments'])
                  ->middleware(['permission:handle split payments']);

            // Pre-order & backorder support
            Route::post('/preorder', [CartController::class, 'handlePreOrder'])
                  ->middleware(['permission:handle preorder']);

            // Buy Now Pay Later (BNPL) integration
            Route::post('/bnpl', [CartController::class, 'handleBuyNowPayLater'])
                  ->middleware(['permission:buy now pay later']);

            // Loyalty points usage
            Route::post('/apply-loyalty-points', [CartController::class, 'applyLoyaltyPoints'])
                  ->middleware(['permission:apply loyalty points']);

            // Secure checkout with fraud detection
            Route::post('/checkout', [CartController::class, 'checkout'])
                  ->middleware(['permission:checkout', 'anti-fraud']);

            // Dynamic shipping cost calculation
            Route::post('/calculate-shipping', [CartController::class, 'calculateShippingCost'])
                  ->middleware(['permission:calculate shipping']);

            // Secure link for sharing cart (signed URL)
            Route::get('/share/{cart_id}', [CartController::class, 'shareCart'])
                  ->middleware(['permission:share cart', 'signed']);
      });
      Route::post('/cart/checkout', [CartController::class, 'checkout']);
});
