<?php

namespace App\Http\Controllers\ProductManagement\AIFeatureProduct;

use App\Http\Controllers\Controller;
use App\Repositories\ProductManagement\AIFeatureProductRepo\UserActivityRepositoryInterface;
use Illuminate\Http\Request;

class UserActivityController extends Controller
{
    
    protected $userActivityRepository;

    public function __construct(UserActivityRepositoryInterface $userActivityRepository)
    {
        $this->userActivityRepository = $userActivityRepository;
    }

    /**
     * Store a new user activity.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'product_id' => 'required|exists:products,id',
                'action' => 'required|in:view,click,purchase,add_to_cart,wishlist,search',
                'device_type' => 'nullable|string',
                'ip_address' => 'nullable|string',
                'additional_data' => 'nullable|string',
            ]);
    
            // Call the repository method to create the user activity
            $userActivity = $this->userActivityRepository->create($validatedData);
    
            return response()->json([
                'message' => 'User activity recorded successfully',
                'data' => $userActivity
            ], 201);
    
        } catch (\Exception $e) {
            \Log::error('Error recording user activity: ' . $e->getMessage());

            return response()->json(['error' => 'An error occurred while recording user activity'], 500);
        }
    }

    /**
     * Get all user activities.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $activities = $this->userActivityRepository->getAll();

        return response()->json([
            'data' => $activities
        ]);
    }

    /**
     * Get user activities by user ID.
     *
     * @param int $userId
     * @return \Illuminate\Http\Response
     */
    public function getByUser(int $userId)
    {
        $activities = $this->userActivityRepository->getByUser($userId);

        return response()->json([
            'data' => $activities
        ]);
    }

    /**
     * Get user activities by product ID.
     *
     * @param int $productId
     * @return \Illuminate\Http\Response
     */
    public function getByProduct(int $productId)
    {
        $activities = $this->userActivityRepository->getByProduct($productId);

        return response()->json([
            'data' => $activities
        ]);
    }

    /**
     * Get most popular products based on user activities.
     *
     * @param int $limit
     * @return \Illuminate\Http\Response
     */
    public function getMostPopularProducts(int $limit)
    {
        $popularProducts = $this->userActivityRepository->getMostPopularProducts($limit);

        return response()->json([
            'data' => $popularProducts
        ]);
    }

    /**
     * Get a user's activity count.
     *
     * @param int $userId
     * @return \Illuminate\Http\Response
     */
    public function getTotalActionsByUser(int $userId)
    {
        $totalActions = $this->userActivityRepository->getTotalActionsByUser($userId);

        return response()->json([
            'data' => $totalActions
        ]);
    }

    /**
     * Get user activities within a date range.
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Http\Response
     */
    public function getByUserAndDateRange(int $userId, string $startDate, string $endDate)
    {
        $activities = $this->userActivityRepository->getByUserAndDateRange($userId, $startDate, $endDate);

        return response()->json([
            'data' => $activities
        ]);
    }

    /**
     * Get product activity count within a date range.
     *
     * @param int $productId
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Http\Response
     */
    public function getProductActivityCountByDateRange(int $productId, string $startDate, string $endDate)
    {
        $activityCount = $this->userActivityRepository->getProductActivityCountByDateRange($productId, $startDate, $endDate);

        return response()->json([
            'data' => $activityCount
        ]);
    }

    /**
     * Get recommended products based on user actions and popularity within categories.
     *
     * @param int $userId
     * @return \Illuminate\Http\Response
     */
    public function getRecommendedProductsForUser(int $userId)
    {
        $recommendedProducts = $this->userActivityRepository->getRecommendedProductsForUser($userId);

        return response()->json([
            'data' => $recommendedProducts
        ]);
    }

    /**
     * Get user activity trends for a specific time frame.
     *
     * @param int $userId
     * @param string $timeFrame
     * @return \Illuminate\Http\Response
     */
    public function getUserActivityTrends(int $userId, string $timeFrame)
    {
        $activityTrends = $this->userActivityRepository->getUserActivityTrends($userId, $timeFrame);

        return response()->json([
            'data' => $activityTrends
        ]);
    }

    /**
     * Get filtered user activity based on device type and IP.
     *
     * @param int $userId
     * @param string|null $deviceType
     * @param string|null $ipAddress
     * @return \Illuminate\Http\Response
     */
    public function getFilteredUserActivity(int $userId, ?string $deviceType = null, ?string $ipAddress = null)
    {
        $filteredActivities = $this->userActivityRepository->getFilteredUserActivity($userId, $deviceType, $ipAddress);

        return response()->json([
            'data' => $filteredActivities
        ]);
    }
}
