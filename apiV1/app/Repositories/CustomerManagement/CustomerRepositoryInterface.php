<?php 

namespace App\Repositories\CustomerManagement;

interface CustomerRepositoryInterface
{
    // Core CRUD
    public function getAllCustomers();
    public function getCustomerById($id);
    public function createCustomer(array $data);
    public function updateCustomer($id, array $data);
    public function deleteCustomer($id);

    // Relationship Queries
    public function getCustomerOrders($customerId);
    public function getCustomerReviews($customerId);
    public function getCustomersByStoreManager($managerId);
    public function getCustomerWithUser($customerId);
    public function getCustomerWithRelations($customerId, array $relations = []);

    // Newsletter & Preferences
    public function getNewsletterSubscribers();
    public function updateNewsletterSubscription($customerId, bool $subscribe);

    // Loyalty & Reward
    public function getCustomerLoyaltyPoints($customerId);
    public function addLoyaltyPoints($customerId, int $points);
    public function redeemLoyaltyPoints($customerId, int $points);

    // Status Management
    public function suspendCustomer($customerId);
    public function activateCustomer($customerId);
    public function markAsInactive($customerId);

    // Soft Delete / Restore (if enabled)
    public function restoreCustomer($customerId);
    public function forceDeleteCustomer($customerId);

    // ✅ Advanced Filtering & Sorting (central method)
    public function advancedFilter(array $filters = [], array $sort = [], int $perPage = 15);

    // Reporting & Statistics
    public function countCustomersByStatus(string $status);
    public function countTotalCustomers();
    public function getRecentCustomers(int $limit = 5);
}