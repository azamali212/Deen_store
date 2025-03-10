<?php
namespace App\Repositories\Coupons;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Collection;

class CouponsRepository implements CouponsRepositoryInterface
{
    // Fetch coupons by any given attributes
    public function findByAttributes(array $attributes): Collection
    {
        return Coupon::where($attributes)->get();
    }

    // Create a coupon
    public function create(array $data): Coupon
    {
        // Check if coupon already exists based on unique fields (like code)
        if (Coupon::where('code', $data['code'])->exists()) {
            throw new \Exception('Coupon code already exists');
        }

        return Coupon::create($data);
    }

    // Update coupon details by ID
    public function update(array $data, $id): Coupon
    {
        $coupon = $this->find($id); // Reuse find method to reduce code duplication
        $coupon->update($data);
        return $coupon;
    }

    // Delete coupon by ID
    public function delete($id): bool
    {
        $coupon = $this->find($id); // Reuse find method
        return $coupon->delete();
    }

    // Find a coupon by its ID, throw exception if not found
    public function find($id): Coupon
    {
        return Coupon::findOrFail($id);
    }

    // Find coupons by user ID (personalized coupons)
    public function findByUserId($user_id): Collection
    {
        return Coupon::where('user_id', $user_id)->get();
    }

    // Find coupons by customer group (e.g., group-specific discounts)
    public function findByCustomerGroup($group_id): Collection
    {
        return Coupon::where('group_id', $group_id)->get();
    }

    // Find valid coupons for a user (checking expiry and usage limits)
    public function findValidCouponsForUser($user_id): Collection
    {
        $currentDate = Carbon::now();
        return Coupon::where('user_id', $user_id)
            ->where('expires_at', '>=', $currentDate)
            ->where('usage_limit', '>', 0)
            ->get();
    }

    // Apply coupon to an order and return the discounted value
    public function applyCoupon($coupon_code, $order_value)
    {
        // Attempt to fetch coupon and perform validation in one step
        $coupon = $this->getValidCoupon($coupon_code);

        // Calculate discount
        $discount = $this->calculateDiscount($coupon, $order_value);

        return $order_value - $discount;
    }

    // Check if a coupon is valid (active, not expired, and not used up)
    public function isValidCoupon($coupon_code): bool
    {
        try {
            $coupon = $this->getValidCoupon($coupon_code);
            return (bool) $coupon;
        } catch (\Exception $e) {
            return false; // Invalid coupon
        }
    }

    // Get the valid coupon or throw exception if not valid
    protected function getValidCoupon($coupon_code): ?Coupon
    {
        $coupon = Cache::remember("coupon:{$coupon_code}", now()->addMinutes(10), function() use ($coupon_code) {
            return Coupon::where('code', $coupon_code)->first();
        });
    
        if (!$coupon) {
            throw new \Exception('Coupon not found');
        }
    
        if ($this->isExpired($coupon_code) || $this->isUsedUp($coupon_code)) {
            throw new \Exception('Coupon not valid or expired');
        }
    
        // Ensure the coupon has a valid discount_type before proceeding
        if (empty($coupon->discount_type)) {
            throw new \Exception('Coupon type is missing or invalid');
        }
    
        return $coupon;
    }

    // Calculate the discount based on coupon type
    protected function calculateDiscount(Coupon $coupon, $order_value): float
    {
        // Check for missing or invalid discount_type
        if (empty($coupon->discount_type)) {
            throw new \Exception('Coupon type is missing or invalid');
        }
    
        switch ($coupon->discount_type) {
            case 'percentage':
                return ($order_value * $coupon->discount_value) / 100;
            case 'fixed':
                return min($coupon->discount_value, $order_value); // Ensure it doesn't exceed order value
            default:
                throw new \Exception('Invalid coupon type');
        }
    }

    // Check if the coupon has expired
    public function isExpired($coupon_code): bool
    {
        $coupon = Coupon::where('code', $coupon_code)->first();
        return $coupon && Carbon::parse($coupon->expires_at)->isPast();
    }

    // Check if the coupon usage limit is reached
    public function isUsedUp($coupon_code): bool
    {
        $coupon = Coupon::where('code', $coupon_code)->first();
        return $coupon && $coupon->usage_limit <= 0;
    }

    // Get active coupons with caching for performance
    public function getActiveCoupons(): Collection
    {
        return Cache::remember('active_coupons', now()->addMinutes(30), function() {
            return Coupon::where('expires_at', '>=', Carbon::now())
                         ->where('usage_limit', '>', 0)
                         ->get();
        });
    }

    // Revoke (deactivate) a coupon
    public function revokeCoupon($coupon_code): bool
    {
        $coupon = Coupon::where('code', $coupon_code)->first();

        if (!$coupon) {
            throw new ModelNotFoundException("Coupon with code {$coupon_code} not found.");
        }

        $coupon->update(['expires_at' => Carbon::now()]); // Set expiration to now
        return true;
    }
}