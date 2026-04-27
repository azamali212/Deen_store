<?php
namespace App\Repositories\ProductManagement\AIFeatureProductRepo;

interface UserActivityRepositoryInterface
{
    /**
     * Create a new user activity.
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Get user activities by user ID.
     *
     * @param int $userId
     * @return mixed
     */
    public function getByUser(int $userId);

    /**
     * Get user activities by product ID.
     *
     * @param int $productId
     * @return mixed
     */
    public function getByProduct(int $productId);

    /**
     * Get all user activities.
     *
     * @return mixed
     */
    public function getAll();

    /**
     * Get the most popular products based on user activities.
     *
     * @param int $limit
     * @return mixed
     */
    public function getMostPopularProducts(int $limit);

    /**
     * Get the total number of actions performed by a user.
     *
     * @param int $userId
     * @return int
     */
    public function getTotalActionsByUser(int $userId);

    /**
     * Get user activities for a specific time range.
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return mixed
     */
    public function getByUserAndDateRange(int $userId, string $startDate, string $endDate);

    /**
     * Get product activity count within a time range.
     *
     * @param int $productId
     * @param string $startDate
     * @param string $endDate
     * @return mixed
     */
    public function getProductActivityCountByDateRange(int $productId, string $startDate, string $endDate);

    /**
     * Get recommended products based on user actions.
     *
     * @param int $userId
     * @return mixed
     */
    public function getRecommendedProductsForUser(int $userId);

    /**
     * Get user activity trends.
     *
     * @param int $userId
     * @param string $timeFrame
     * @return mixed
     */
    public function getUserActivityTrends(int $userId, string $timeFrame);

    public function getFilteredUserActivity(int $userId, ?string $deviceType = null, ?string $ipAddress = null);
}