<?php

namespace App\Http\Controllers\Cart;

use App\Events\CartAbandonedEvent;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\User;
use App\Notifications\CartReminderNotification;
use App\Repositories\Cart\CartRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Notification;

class CartController extends Controller
{
    protected $cartRepo;

    public function __construct(CartRepositoryInterface $cartRepo)
    {
        $this->cartRepo = $cartRepo;
    }

    // Get Cart
    public function getCart(Request $request)
    {
        try {
            $user_id = $request->user()->id; // Assuming authentication is set up
            $cart_token = $request->header('X-Cart-Token'); // Pass the cart token from client-side request headers

            if (!$cart_token) {
                return response()->json(['error' => 'Cart token is required'], 400);
            }

            $cart = $this->cartRepo->getCart($user_id, $cart_token);

            return response()->json(['cart' => $cart], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error fetching cart: ' . $e->getMessage()], 500);
        }
    }

    // Add to Cart
    public function addToCart(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $product_id = $request->product_id;
            $variant_id = $request->variant_id;
            $quantity = $request->quantity;
            $attributes = $request->attributes ?? [];

            $cartItem = $this->cartRepo->addToCart($user_id, $product_id, $variant_id, $quantity, $attributes);

            // Ensure quantity is included in the response
            $cartItemData = $cartItem->toArray();
            $cartItemData['quantity'] = (int) $cartItem->quantity;

            return response()->json([
                'message' => 'Product added to cart',
                'cart_item' => $cartItemData
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error adding to cart: ' . $e->getMessage()], 500);
        }
    }

    // Update Cart Item
    public function updateCart(Request $request, $cart_item_id)
    {
        try {
            $user_id = $request->user()->id;
            $quantity = $request->quantity;
            $attributes = $request->attributes ?? [];

            $cartItem = $this->cartRepo->updateCart($user_id, $cart_item_id, $quantity, $attributes);

            return response()->json(['message' => 'Cart updated', 'cart_item' => $cartItem], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error updating cart: ' . $e->getMessage()], 500);
        }
    }

    // Delete Cart Item
    public function deleteCartItem(Request $request, $cart_item_id)
    {
        try {
            $user_id = $request->user()->id;
            $this->cartRepo->deleteCartItem($user_id, $cart_item_id);

            return response()->json(['message' => 'Cart item removed'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error deleting cart item: ' . $e->getMessage()], 500);
        }
    }

    // Clear Cart
    public function clearCart($user_id)
    {
        try {
            $this->cartRepo->clearCart($user_id);
            return response()->json(['message' => 'Cart cleared successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error clearing cart: ' . $e->getMessage()], 500);
        }
    }

    // Apply Discount
    public function applyDiscount(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $coupon_code = $request->coupon_code;
            //dd($coupon_code);

            $cart = $this->cartRepo->applyDiscount($user_id, $coupon_code);

            return response()->json(['message' => 'Discount applied', 'cart' => $cart], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error applying discount: ' . $e->getMessage()], 500);
        }
    }
    public function autoApplyBestDiscount(Request $request)
    {
        try {
            $user_id = $request->user()->id;  // Retrieve the authenticated user's ID

            // Attempt to apply the best discount
            $response = $this->cartRepo->applyDiscount($user_id, 'BEST_DISCOUNT_CODE');

            // Check if the response is an instance of JsonResponse
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $responseData = $response->getData(true); // Retrieve data as an array

                if (isset($responseData['error'])) {
                    // Handle error scenario (coupon not found or expired)
                    return response()->json(['error' => $responseData['error']], 404);
                }

                return response()->json([
                    'message' => 'Best discount applied successfully',
                    'cart' => $responseData['cart'],
                ]);
            }

            return response()->json(['error' => 'Unexpected response from applyDiscount method'], 500);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error applying best discount: ' . $e->getMessage()
            ], 500);
        }
    }

    // Calculate Tax
    public function calculateTax(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $cart = $this->cartRepo->calculateTax($user_id);

            return response()->json(['message' => 'Tax calculated', 'cart' => $cart], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error calculating tax: ' . $e->getMessage()], 500);
        }
    }

    // Merge Guest Cart to User
    public function mergeGuestCartToUser(Request $request)
    {
        try {

            $user_id = $request->user()->id;
            //dd($user_id);
            $cart_token = $request->header('X-Cart-Token'); // Pass the cart token from client-side request headers
            //dd($cart_token);

            if (!$cart_token) {
                return response()->json(['error' => 'Cart token is required'], 400);
            }

            $guestCart = $this->cartRepo->getCart($user_id, $cart_token);

            if (!$guestCart) {
                return response()->json(['error' => 'Guest cart not found'], 404);
            }

            return response()->json(['message' => 'Guest cart merged'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error merging guest cart: ' . $e->getMessage()], 500);
        }
    }

    // Move Item to Wishlist
    public function moveToWishlist(Request $request, $cart_item_id)
    {
        try {
            $user_id = $request->user()->id;
            $this->cartRepo->moveToWishlist($user_id, $cart_item_id);

            return response()->json(['message' => 'Item moved to wishlist'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error moving item to wishlist: ' . $e->getMessage()], 500);
        }
    }

    // Get Personalized Recommendations
    public function getPersonalizedRecommendations(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $recommendations = $this->cartRepo->getPersonalizedRecommendations($user_id);

            return response()->json(['recommendations' => $recommendations], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error fetching recommendations: ' . $e->getMessage()], 500);
        }
    }

    // Estimate Delivery Date
    public function estimateDeliveryDate(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $estimatedDate = $this->cartRepo->estimateDeliveryDate($user_id);

            return response()->json(['estimated_delivery_date' => $estimatedDate], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error estimating delivery date: ' . $e->getMessage()], 500);
        }
    }

    // Handle Subscription Items
    public function handleSubscriptionItems(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $subscriptionStatus = $this->cartRepo->handleSubscriptionItems($user_id);

            return response()->json(['message' => $subscriptionStatus], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error handling subscription items: ' . $e->getMessage()], 500);
        }
    }

    // Save Cart State
    public function saveCartState(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $stateStatus = $this->cartRepo->saveCartState($user_id);

            return response()->json(['message' => $stateStatus], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error saving cart state: ' . $e->getMessage()], 500);
        }
    }

    // Reorder Past Cart
    public function reorderPastCart(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $reorderStatus = $this->cartRepo->reorderPastCart($user_id);

            return response()->json(['message' => $reorderStatus], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error reordering past cart: ' . $e->getMessage()], 500);
        }
    }

    // Handle Split Payments
    public function handleSplitPayments(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $paymentDetails = $request->payment_details;
            $splitPaymentStatus = $this->cartRepo->handleSplitPayments($user_id, $paymentDetails);

            return response()->json(['message' => $splitPaymentStatus], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error handling split payments: ' . $e->getMessage()], 500);
        }
    }
    public function validateStockBeforeCheckout(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $cart = $this->cartRepo->validateStockBeforeCheckout($user_id);

            return response()->json(['message' => 'Stock validated', 'cart' => $cart], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error validating stock: ' . $e->getMessage()], 500);
        }
    }
    public function applyCurrencyConversion(Request $request)
    {
        try {
            $user_id = $request->user()->id;
            $currency = $request->currency;
            $cart = $this->cartRepo->applyCurrencyConversion($user_id, $currency);

            return response()->json(['message' => 'Currency conversion applied', 'cart' => $cart], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error recovering abandoned carts: ' . $e->getMessage()], 500);
        }
    }
    public function recoverAbandonedCarts($userId)
    {
        // Ensure the user exists
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Fetch all abandoned carts without filtering by `updated_at`
        $abandonedCarts = Cart::where('status', 'abandoned')
            ->where('user_id', $userId)
            ->get();

        if ($abandonedCarts->isEmpty()) {
            return response()->json(['message' => 'No abandoned carts found'], 404);
        }

        foreach ($abandonedCarts as $cart) {
            Log::info("Sending abandoned cart reminder to user: " . $cart->user_id);

            // Send email and notification
            event(new CartAbandonedEvent($cart));
            Notification::send($cart->user, new CartReminderNotification($cart));
        }

        return response()->json([
            'message' => 'Abandoned cart reminders sent successfully.',
            'carts' => $abandonedCarts
        ], 200);
    }
}
