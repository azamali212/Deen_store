<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Cart\CartRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CartRepositoryTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    protected $cartRepository;
    protected $user;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartRepository = new CartRepository();
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create(['price' => 100, 'stock' => 10]);
    }

    /** @test */
    public function it_can_add_a_product_to_the_cart()
    {
        $cartItem = $this->cartRepository->addToCart($this->user->id, $this->product->id, null, 2);

        $this->assertNotNull($cartItem);
        $this->assertEquals(2, $cartItem->quantity);
        $this->assertEquals(200, $cartItem->total_price);
    }

    /** @test */
    public function it_can_update_cart_item_quantity()
    {
        $cartItem = $this->cartRepository->addToCart($this->user->id, $this->product->id, null, 1);
        $updatedItem = $this->cartRepository->updateCart($this->user->id, $cartItem->id, 5);

        $this->assertEquals(5, $updatedItem->quantity);
        $this->assertEquals(500, $updatedItem->total_price);
    }

    /** @test */
    public function it_can_delete_a_cart_item()
    {
        $cartItem = $this->cartRepository->addToCart($this->user->id, $this->product->id, null, 1);
        $this->cartRepository->deleteCartItem($this->user->id, $cartItem->id);

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    /** @test */
    public function it_can_clear_the_cart()
    {
        $this->cartRepository->addToCart($this->user->id, $this->product->id, null, 1);
        $this->cartRepository->clearCart($this->user->id);

        $this->assertDatabaseMissing('cart_items', ['cart_id' => Cart::where('user_id', $this->user->id)->value('id')]);
    }

    /** @test */
    public function it_can_apply_a_discount()
    {
        Coupon::factory()->create(['code' => 'DISCOUNT10', 'discount_percentage' => 10]);

        $this->cartRepository->addToCart($this->user->id, $this->product->id, null, 1);
        $cart = $this->cartRepository->applyDiscount($this->user->id, 'DISCOUNT10');

        $this->assertEquals(90, $cart->total_price);
    }

    /** @test */
    public function it_throws_error_for_invalid_coupon()
    {
        $this->cartRepository->addToCart($this->user->id, $this->product->id, null, 1);

        $this->expectException(\Exception::class);
        $this->cartRepository->applyDiscount($this->user->id, 'INVALID_COUPON');
    }

    /** @test */
    public function it_can_merge_guest_cart_to_user_cart()
    {
        $guestSessionId = 'guest_123';
        $guestCart = Cart::factory()->create(['session_id' => $guestSessionId]);
        CartItem::factory()->create(['cart_id' => $guestCart->id, 'product_id' => $this->product->id, 'quantity' => 1]);

        $this->cartRepository->mergeGuestCartToUser($guestSessionId, $this->user->id);

        $this->assertDatabaseMissing('carts', ['session_id' => $guestSessionId]);
        $this->assertDatabaseHas('cart_items', ['cart_id' => Cart::where('user_id', $this->user->id)->value('id')]);
    }

    /** @test */
    public function it_can_validate_stock_before_checkout()
    {
        $this->cartRepository->addToCart($this->user->id, $this->product->id, null, 5);

        $this->assertTrue($this->cartRepository->validateStockBeforeCheckout($this->user->id));
    }

    /** @test */
    public function it_throws_error_when_product_is_out_of_stock()
    {
        $this->cartRepository->addToCart($this->user->id, $this->product->id, null, 11);

        $this->expectException(\Exception::class);
        $this->cartRepository->validateStockBeforeCheckout($this->user->id);
    }

    /** @test */
    public function it_can_get_personalized_recommendations()
    {
        Product::factory()->count(5)->create();
        $recommendations = $this->cartRepository->getPersonalizedRecommendations($this->user->id);

        $this->assertCount(5, $recommendations);
    }

    /** @test */
    public function it_can_estimate_delivery_date()
    {
        $estimatedDate = $this->cartRepository->estimateDeliveryDate($this->user->id);

        $this->assertEquals(now()->addDays(5)->format('Y-m-d'), $estimatedDate);
    }

    /** @test */
    public function it_can_handle_subscription_items()
    {
        $result = $this->cartRepository->handleSubscriptionItems($this->user->id);

        $this->assertEquals("Subscription processing logic here", $result);
    }

    /** @test */
    public function it_can_save_cart_state()
    {
        $result = $this->cartRepository->saveCartState($this->user->id);

        $this->assertEquals("Cart state saved successfully!", $result);
    }

    /** @test */
    public function it_can_reorder_past_cart()
    {
        $result = $this->cartRepository->reorderPastCart($this->user->id);

        $this->assertEquals("Reorder previous cart logic here!", $result);
    }

    /** @test */
    public function it_can_handle_split_payments()
    {
        $result = $this->cartRepository->handleSplitPayments($this->user->id, []);

        $this->assertEquals("Split payment processing logic here!", $result);
    }
}
