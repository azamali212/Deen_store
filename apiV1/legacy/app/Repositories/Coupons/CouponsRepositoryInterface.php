<?php
namespace App\Repositories\Coupons;

interface CouponsRepositoryInterface
{
    // General method to fetch coupons with flexible conditions
    public function findByAttributes(array $attributes);

    // Method for creating a coupon
    public function create(array $data);

    // Method for updating a coupon
    public function update(array $data, $id);

    // Method for deleting a coupon
    public function delete($id);

    // Method for finding a coupon by its ID
    public function find($id);

    // Method to find coupons by user ID (e.g., personalized coupons)
    public function findByUserId($user_id);

    // Method to find coupons by customer group (e.g., group-specific discounts)
    public function findByCustomerGroup($group_id);

    // Method to find valid coupons for a user (checking expiry and usage limits)
    public function findValidCouponsForUser($user_id);

    // Method to apply a coupon to an order value and return the discount
    public function applyCoupon($coupon_code, $order_value);

    // Method to check if a coupon is valid
    public function isValidCoupon($coupon_code);

    // Method to check if a coupon is expired
    public function isExpired($coupon_code);

    // Method to check if a coupon has been used the maximum number of times
    public function isUsedUp($coupon_code);

    // Method to get all coupons related to a specific product or category
    //public function findCouponsByProduct($product_id);
}