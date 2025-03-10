<?php

namespace App\Repositories\Cart;

interface CartRepositoryInterface
{
    // Retrieve the cart for a user (or guest via session)
    public function getCart($user_id, $cart_token = null);

    // Add a product to the cart
    public function addToCart($user_id, $product_id, $variant_id, $quantity, $attributes = []);

    // Update cart item quantity or attributes
    public function updateCart($user_id, $cart_item_id, $quantity, $attributes = []);

    // Remove an item from the cart
    public function deleteCartItem($user_id, $cart_item_id);

    // Empty the entire cart
    public function clearCart($user_id);

    // Apply discount codes or vouchers
    public function applyDiscount($user_id, $coupon_code);

    // Calculate tax dynamically based on location and cart contents
    public function calculateTax($user_id);

    // Convert a guest cart (session-based) to a user cart (after login)
    public function mergeGuestCartToUser($session_id, $user_id);

    // Move an item from the cart to the user's wishlist
    public function moveToWishlist($user_id, $cart_item_id);

    // Recover abandoned carts and send reminders
    public function recoverAbandonedCarts($userId);

    // **🚀 New Advanced Features (2025)**
    
    // AI-powered cart recommendations based on purchase history & trends
    public function getPersonalizedRecommendations($user_id);

    // Validate product stock in real-time before checkout
    public function validateStockBeforeCheckout($user_id);

    // Apply multi-currency conversions dynamically
    public function applyCurrencyConversion($user_id, $currency);

    // Automatically apply the best available discount or coupon
    public function autoApplyBestDiscount($user_id);

    // Estimate delivery dates based on location and cart items
    public function estimateDeliveryDate($user_id);

    // Handle subscription-based products in the cart
    public function handleSubscriptionItems($user_id);

    // Store the last cart state before checkout for recovery
    public function saveCartState($user_id);

    // Allow one-click reorder for past cart items
    public function reorderPastCart($user_id);

    // Handle split payments or installment payments in the cart
    public function handleSplitPayments($user_id, $payment_details);
}