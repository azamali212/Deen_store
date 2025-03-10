<?php

namespace App\Http\Controllers\Gift;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Repositories\Coupons\CouponsRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CouponsController extends Controller
{
    protected $couponsRepository;

    public function __construct(CouponsRepositoryInterface $couponsRepositoryInterface)
    {
        $this->couponsRepository = $couponsRepositoryInterface;
    }

    public function getCouponsByUser($user_id)
    {
        return response()->json([
            'data' => $this->couponsRepository->findByUserId($user_id)
        ]);
    }

    // Apply a coupon to an order
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'order_value' => 'required|numeric|min:0'
        ]);
    
        try {
            $newOrderValue = $this->couponsRepository->applyCoupon($request->coupon_code, $request->order_value);
            return response()->json([
                'success' => true,
                'new_order_value' => $newOrderValue
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Check if a coupon is valid
    public function checkCouponValidity(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string'
        ]);

        $isValid = $this->couponsRepository->isValidCoupon($request->coupon_code);

        return response()->json([
            'valid' => $isValid
        ]);
    }

    public function checkUsagLimit(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string'
        ]);

        // Find the coupon by its code
        $coupon = Coupon::where('code', $request->coupon_code)->first();

        // If coupon is not found, return an error message
        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found'
            ], 404);
        }

        // Check if the coupon usage limit is used up
        $isUsedUp = $this->couponsRepository->isUsedUp($request->coupon_code);

        // Prepare the response with detailed information
        return response()->json([
            'success' => true,
            'message' => $isUsedUp ? 'The coupon usage limit has been reached.' : 'The coupon usage limit is still available.',
            'data' => [
                'coupon_code' => $request->coupon_code,
                'usage_limit' => $coupon->usage_limit,
                'is_used_up' => $isUsedUp,
                'remaining_usage_limit' => $coupon->usage_limit > 0 ? $coupon->usage_limit : 0
            ]
        ]);
    }

    public function isExpire(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string'
        ]);

        // Find the coupon by its code
        $coupon = Coupon::where('code', $request->coupon_code)->first();

        // If coupon is not found, return an error message
        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found'
            ], 404);
        }

        // Check if the coupon has expired using the existing method
        $isExpired = $this->couponsRepository->isExpired($request->coupon_code);

        return response()->json([
            'success' => true,
            'message' => $isExpired ? 'The coupon has expired.' : 'The coupon is still valid.',
            'data' => [
                'expire' => $isExpired,
                'expires_at' => $coupon->expires_at->toDateTimeString(),
                'time_left' => $isExpired ? null : Carbon::parse($coupon->expires_at)->diffForHumans() // Calculate the time remaining if not expired
            ]
        ]);
    }

    // Create a new coupon
    public function createCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons',
            'discount_value' => 'required|numeric|min:0',
            'type' => 'required|string|in:percentage,fixed',
            'expires_at' => 'required|date',
            'usage_limit' => 'required|integer|min:1'
        ]);

        $coupon = $this->couponsRepository->create($request->all());

        return response()->json([
            'success' => true,
            'coupon' => $coupon
        ]);
    }

    // Update an existing coupon
    public function updateCoupon(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|string',
            'discount_value' => 'required|numeric|min:0',
            'type' => 'required|string|in:percentage,fixed',
            'expires_at' => 'required|date',
            'usage_limit' => 'required|integer|min:1'
        ]);

        $coupon = $this->couponsRepository->update($request->all(), $id);

        return response()->json([
            'success' => true,
            'coupon' => $coupon
        ]);
    }



    // Delete a coupon
    public function deleteCoupon($id)
    {
        $this->couponsRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully'
        ]);
    }
}
