<?php

namespace App\Repositories\Cart;

use App\Events\CartAbandonedEvent;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Notifications\CartReminderNotification;
use App\Repositories\Order\OrderRepositoryInterface;
use App\Repositories\PaymentSystem\StripePaymentRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CartRepository implements CartRepositoryInterface
{
    protected StripePaymentRepositoryInterface $stripePaymentRepository;
    protected OrderRepositoryInterface $orderRepository;
    public function __construct(StripePaymentRepositoryInterface $stripePaymentRepository, OrderRepositoryInterface $orderRepository)
    {
        $this->stripePaymentRepository = $stripePaymentRepository;
        $this->orderRepository = $orderRepository;
    }
    public function getCart($user_id, $cart_token = null)
    {
        // Using cache to avoid repeated DB hits
        return Cache::remember("cart_{$user_id}_{$cart_token}", now()->addMinutes(10), function () use ($user_id, $cart_token) {
            return Cart::where('user_id', $user_id)
                ->orWhere('cart_token', $cart_token)
                ->with('cartItems.product') // Eager load the product for faster access
                ->first();
        });
    }

    public function addToCart($user_id, $product_id, $variant_id, $quantity, $attributes = [])
    {
        DB::beginTransaction();
        try {
            $cart = Cart::firstOrCreate(
                ['user_id' => $user_id],
                [
                    'cart_token' => \Str::random(32),
                    'total_price' => 0,
                    'total_quantity' => 0
                ]
            );
    
            $product = Product::findOrFail($product_id);
    
            $cartItem = CartItem::updateOrCreate(
                [
                    'cart_id' => $cart->id,
                    'product_id' => $product_id,
                    'variant_id' => $variant_id,
                ],
                [
                    'price' => $product->price,
                    'attributes' => json_encode($attributes),
                ]
            );
    
            $cartItem->quantity += $quantity;
            $cartItem->save();
    
            $this->updateCartTotals($cart);
    
            event(new CartAbandonedEvent($cart));
            Notification::send($cart->user, new CartReminderNotification($cart));
    
            DB::commit();
            return $cartItem;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error adding to cart', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateCart($user_id, $cart_item_id, $quantity, $attributes = [])
    {
        DB::beginTransaction();
        try {
            $cartItem = CartItem::whereHas('cart', function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })->findOrFail($cart_item_id);

            // Update quantity and attributes, and let MySQL handle `total_price`
            $cartItem->update([
                'quantity' => $quantity,
                'attributes' => json_encode($attributes),
            ]);

            $this->updateCartTotals($cartItem->cart);

            DB::commit();
            return $cartItem;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating cart', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleteCartItem($user_id, $cart_item_id)
    {
        $cartItem = CartItem::whereHas('cart', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->findOrFail($cart_item_id);

        $cartItem->delete();

        $this->updateCartTotals($cartItem->cart);
    }

    public function clearCart($user_id)
    {
        // Find the cart for the user
        $cart = Cart::where('user_id', $user_id)->first();

        if (!$cart) {
            throw new Exception('Cart not found');
        }

        // Ensure the cart has items before trying to delete
        $cartItems = $cart->cartItems()->get(); // Get all cart items
        if ($cartItems->isEmpty()) {
            throw new Exception('No items found in cart');
        }

        // Delete all cart items associated with the cart
        $cart->cartItems()->delete(); // Use cartItems() instead of items()

        // Reset the cart totals
        $cart->update(['total_price' => 0, 'total_quantity' => 0]);

        return response()->json(['message' => 'Cart cleared successfully'], 200);
    }

    public function applyDiscount($user_id, $coupon_code)
    {
        DB::beginTransaction();
        try {
            if (empty($coupon_code)) {
                throw new Exception('Coupon code cannot be empty');
            }

            $cart = Cart::where('user_id', $user_id)->firstOrFail();
            $coupon = Coupon::where('code', $coupon_code)
                ->where('is_active', 1)
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now())
                ->whereColumn('used_count', '<', 'usage_limit')
                ->first();

            if (!$coupon) {
                throw new Exception('Coupon not found or expired');
            }

            // Calculate discount based on coupon type
            $discount = $coupon->discount_type === 'fixed'
                ? ($cart->total_price * $coupon->discount_value) / 100
                : min($coupon->discount_value, $cart->total_price);

            // Update cart totals
            $cart->update([
                'discount_amount' => $discount,
                'total_price' => max(0, $cart->total_price - $discount),
            ]);

            $coupon->increment('used_count');
            DB::commit();

            return response()->json([
                'message' => 'Discount applied successfully',
                'cart' => $cart->fresh() // Return updated cart details
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error applying discount', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error applying discount: ' . $e->getMessage()], 500);
        }
    }

    public function calculateTax($user_id)
    {
        $cart = Cart::where('user_id', $user_id)->firstOrFail();
        $tax_rate = 0.1; // Example: 10% tax
        $tax = $cart->total_price * $tax_rate;

        $cart->update(['tax_amount' => $tax, 'total_price' => $cart->total_price + $tax]);

        return $cart;
    }

    public function mergeGuestCartToUser($cart_token, $user_id)
    {
        // Retrieve guest cart
        $guestCart = Cart::where('cart_token', $cart_token)->first();

        if (!$guestCart) {
            throw new Exception('Guest cart not found');
        }

        // Merge guest cart items to the user's cart
        $userCart = Cart::where('user_id', $user_id)->first();

        if (!$userCart) {
            throw new Exception('User cart not found');
        }

        // Assuming Cart has a relation `cartItems`
        foreach ($guestCart->cartItems as $guestItem) {
            // You may want to add logic to handle merging items (if items already exist in the user's cart)
            $this->addToCart($user_id, $guestItem->product_id, $guestItem->variant_id, $guestItem->quantity, json_decode($guestItem->attributes, true));
        }

        // Optionally delete guest cart items after merging
        $guestCart->cartItems()->delete();

        return $userCart;
    }

    public function moveToWishlist($user_id, $cart_item_id)
    {
        // Find the cart item by user and item ID
        $cartItem = CartItem::whereHas('cart', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->findOrFail($cart_item_id);

        // Check if the user already has a wishlist, if not, create one
        $wishlist = Wishlist::firstOrCreate([
            'user_id' => $user_id,
        ]);

        // Create a new WishlistItem and move it to the wishlist
        $wishlistItem = new WishlistItem();
        $wishlistItem->wishlist_id = $wishlist->id;  // Assign the wishlist ID
        $wishlistItem->user_id = $user_id;  // Store the user ID
        $wishlistItem->product_id = $cartItem->product_id;  // Store the product ID
        $wishlistItem->quantity = $cartItem->quantity;  // If you want to move the quantity to the wishlist
        $wishlistItem->save();  // Save the new WishlistItem

        // Delete the CartItem after moving it to the wishlist
        $cartItem->delete();

        return response()->json(['message' => 'Item successfully moved to wishlist'], 200);
    }


    public function recoverAbandonedCarts($userId)
    {
        $abandonedCarts = Cart::where('status', 'abandoned')
            ->where('updated_at', '<', now()->subDays(3))
            ->where('user_id', $userId)
            ->get();

        foreach ($abandonedCarts as $cart) {
            Log::info("Sending abandoned cart reminder to user: " . $cart->user_id);

            // Send email and notification
            event(new CartAbandonedEvent($cart));
            Notification::send($cart->user, new CartReminderNotification($cart));
        }

        return $abandonedCarts; // Return the carts
    }

    // 🚀 New Features

    public function getPersonalizedRecommendations($user_id)
    {
        return Product::inRandomOrder()->limit(5)->get(); // Replace with AI model logic
    }

    public function validateStockBeforeCheckout($user_id)
    {
        $cart = Cart::where('user_id', $user_id)->with('cartItems')->first();

        foreach ($cart->cartItems as $item) {
            if ($item->product->stock < $item->quantity) {
                throw new Exception("Product {$item->product->name} is out of stock.");
            }
        }

        return true;
    }

    public function applyCurrencyConversion($user_id, $currency)
    {
        $cart = Cart::where('user_id', $user_id)->firstOrFail();
        $exchangeRate = $this->getExchangeRate($currency); // Example function
        $cart->update(['total_price' => $cart->total_price * $exchangeRate]);

        return $cart;
    }

    public function autoApplyBestDiscount($user_id)
    {
        // Fetch best discount logic here
        return $this->applyDiscount($user_id, 'BEST_DISCOUNT_CODE');
    }

    public function estimateDeliveryDate($user_id)
    {
        return now()->addDays(5)->format('Y-m-d'); // Example: 5-day estimated delivery
    }

    public function handleSubscriptionItems($user_id)
    {
        return "Subscription processing logic here";
    }

    public function saveCartState($user_id)
    {
        return "Cart state saved successfully!";
    }

    public function reorderPastCart($user_id)
    {
        return "Reorder previous cart logic here!";
    }

    public function handleSplitPayments($user_id, $payment_details)
    {
        return "Split payment processing logic here!";
    }

    // Helper Function: Update Cart Totals
    private function updateCartTotals($cart)
    {
        $totalPrice = $cart->cartItems()->sum('total_price');
        $totalQuantity = $cart->cartItems()->sum('quantity');
        $cart->update(['total_price' => $totalPrice, 'total_quantity' => $totalQuantity]);
    }

    // Helper Function: Get Exchange Rate (Example)
    private function getExchangeRate($currency)
    {
        return $currency === 'EUR' ? 0.85 : 1.00; // Example: 1 USD = 0.85 EUR
    }

    public function checkout(User $user, string $paymentMethodId): bool
    {
        DB::beginTransaction();

        try {
            Log::info('User ID: ' . $user->id);
            $cart = Cart::with(['cartItems.product', 'cartItems.variant'])->where('user_id', $user->id)->first();
            Log::info('Cart: ', [$cart]);
            Log::info('Cart Items Count: ' . optional($cart)->cartItems->count());

            if (!$cart || $cart->cartItems->isEmpty()) {
                throw new Exception('Your cart is empty.');
            }

            $amountInCents = $cart->cartItems->sum(function ($item) {
                return $item->price * $item->quantity * 100;
            });

            if ($amountInCents <= 0) {
                throw new Exception('Invalid total amount.');
            }

            // 3. Create Stripe customer if not already
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer();
            }

            // 4. Attach and validate payment method
            try {
                $this->stripePaymentRepository->createOrAttachPaymentMethod($user, null, $paymentMethodId);
            } catch (Exception $e) {
                // Handle the error if the payment method is not reusable
                if ($e->getMessage() == 'This payment method is not reusable. Please provide a new payment method.') {
                    throw new Exception('This payment method is not reusable. Please provide a new payment method.');
                }
                throw new Exception('Unable to process payment method: ' . $e->getMessage());
            }

            $chargeResponse = $this->stripePaymentRepository->chargeCustomer($user, $amountInCents, 'usd', $paymentMethodId);

            if ($chargeResponse['status'] !== 'succeeded') {
                throw new Exception('Payment failed. Please try again.');
            }

            $orderData = [
                'user_id' => $user->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'grand_total' => $amountInCents / 100,
                'order_status' => 'processing',
                'payment_status' => 'paid',
                'shipping_address' => $cart->shipping_address ?? 'N/A',
                'billing_address' => $cart->billing_address ?? 'N/A',
                'order_items' => $cart->cartItems->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'attributes' => $item->attributes,
                        'product_name' => $item->product->name,
                        'total_price' => $item->price * $item->quantity,
                    ];
                })->toArray(),
            ];

            // 🎯 Just call the order repository
            $this->orderRepository->createOrder($orderData);

            // 🧹 Clear cart
            $cart->cartItems()->delete();

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Checkout failed: ' . $e->getMessage());

            throw new Exception('Checkout failed: ' . $e->getMessage());
        }
    }
}
