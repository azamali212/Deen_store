<?php

namespace App\Repositories\Order\Order_tracking;

interface OrderTrackingRepositoryInterface
{
     /**
     * Track the latest status of an order
     */
    public function getLatestStatusByOrderId(int $orderId): ?array;

    /**
     * Get full tracking history for an order
     */
    public function getTrackingHistory(int $orderId): array;

    /**
     * Add a new tracking entry
     */
    public function createTrackingEntry(array $data): bool;

    /**
     * Update an existing tracking record (e.g., location/status)
     */
    public function updateTracking(int $trackingId, array $data): bool;

    /**
     * Delete a tracking entry (admin only)
     */
    public function deleteTrackingEntry(int $trackingId): bool;

    /**
     * Bulk update statuses (for courier API sync)
     */
    public function bulkUpdateStatuses(array $trackings): bool;

    /**
     * Get all tracking updates by a specific status (e.g., "out for delivery")
     */
    public function getTrackingsByStatus(string $status): array;

    /**
     * Get tracking entries updated within a date range
     */
    public function getTrackingsByDateRange(string $startDate, string $endDate): array;

    /**
     * Get all orders currently at a specific location
     */
    public function getTrackingsByLocation(string $location): array;

    /**
     * Check if an order has reached a specific status
     */
    public function hasStatus(int $orderId, string $status): bool;

    /**
     * Get the latest location of a given order
     */
    public function getLatestLocation(int $orderId): ?string;

    /**
     * Get delivery performance statistics by delivery manager
     */
    public function getDeliveryStatsByManager(int $managerId): array;

    /**
     * Search order tracking by keywords/status/location etc.
     */
    public function searchTrackings(array $filters): array;

    /**
     * Sync tracking data from external APIs
     */
    public function syncWithCourier(array $apiData): bool;
}