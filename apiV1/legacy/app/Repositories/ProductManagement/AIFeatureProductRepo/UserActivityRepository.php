<?php

namespace App\Repositories\ProductManagement\AIFeatureProductRepo;

use App\Models\UserActivity;
use App\Models\Product;
use App\Models\Category;
use Carbon\Carbon;

class UserActivityRepository implements UserActivityRepositoryInterface
{
    /**
     * Create a new user activity.
     *
     * @param array $data
     * @return UserActivity
     */
    public function create(array $data)
    {
        $product = Product::find($data['product_id']);
        if (!$product) {
            throw new \Exception('Product not found.');
        }

        // Enhanced logic to include metadata, like device type and IP
        return UserActivity::create([
            'user_id' => $data['user_id'],
            'product_id' => $data['product_id'],
            'category_id' => Product::find($data['product_id'])->category_id,
            'action' => $data['action'],
            'action_time' => Carbon::now(),
            'device_type' => $data['device_type'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'additional_data' => $data['additional_data'] ?? null
        ]);
    }

    /**
     * Get user activities by user ID.
     *
     * @param int $userId
     * @return mixed
     */
    public function getByUser(int $userId)
    {
        return UserActivity::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get user activities by product ID.
     *
     * @param int $productId
     * @return mixed
     */
    public function getByProduct(int $productId)
    {
        return UserActivity::where('product_id', $productId)->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get all user activities.
     *
     * @return mixed
     */
    public function getAll()
    {
        return UserActivity::latest()->get();
    }

    /**
     * Get the most popular products based on user activities.
     *
     * @param int $limit
     * @return mixed
     */
    public function getMostPopularProducts(int $limit)
    {
        return UserActivity::select('product_id', \DB::raw('count(*) as total'))
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->take($limit)
            ->get()
            ->map(function ($item) {
                return Product::find($item->product_id);
            });
    }

    /**
     * Get the total number of actions performed by a user.
     *
     * @param int $userId
     * @return int
     */
    public function getTotalActionsByUser(int $userId)
    {
        return UserActivity::where('user_id', $userId)->count();
    }

    /**
     * Get user activities for a specific time range.
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return mixed
     */
    public function getByUserAndDateRange(int $userId, string $startDate, string $endDate)
    {
        return UserActivity::where('user_id', $userId)
            ->whereBetween('created_at', [Carbon::parse($startDate), Carbon::parse($endDate)])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get product activity count within a time range.
     *
     * @param int $productId
     * @param string $startDate
     * @param string $endDate
     * @return int
     */
    public function getProductActivityCountByDateRange(int $productId, string $startDate, string $endDate)
    {
        return UserActivity::where('product_id', $productId)
            ->whereBetween('created_at', [Carbon::parse($startDate), Carbon::parse($endDate)])
            ->count();
    }

    /**
     * Get recommended products based on user actions and popularity within categories.
     *
     * @param int $userId
     * @return mixed
     */
    public function getRecommendedProductsForUser(int $userId)
    {
        // Get user activity data for recommendations
        $activities = UserActivity::where('user_id', $userId)->get();
        $categoryIds = $activities->pluck('product.category_id')->unique();

        // Get popular products within those categories, but exclude already viewed products
        return Product::whereIn('category_id', $categoryIds)
            ->whereNotIn('id', $activities->pluck('product_id'))
            ->orderByDesc('popularity') // Assuming you have a popularity field in Product
            ->take(10)
            ->get();
    }

    /**
     * Get user activity trends based on a specific time frame.
     *
     * @param int $userId
     * @param string $timeFrame
     * @return mixed
     */
    public function getUserActivityTrends(int $userId, string $timeFrame)
    {
        $startDate = Carbon::now()->sub($timeFrame);
        $endDate = Carbon::now();

        return UserActivity::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(\DB::raw('DATE(created_at) as date'), \DB::raw('count(*) as actions'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get a user's activity history with advanced filters (device type, IP, etc.)
     *
     * @param int $userId
     * @param string|null $deviceType
     * @param string|null $ipAddress
     * @return mixed
     */
    public function getFilteredUserActivity(int $userId, ?string $deviceType = null, ?string $ipAddress = null)
    {
        $query = UserActivity::where('user_id', $userId);

        if ($deviceType) {
            $query->where('device_type', $deviceType);
        }

        if ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
