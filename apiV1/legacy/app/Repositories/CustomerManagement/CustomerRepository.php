<?php

namespace App\Repositories\CustomerManagement;

use App\Models\Customer;

class CustomerRepository implements CustomerRepositoryInterface
{
    // Core CRUD
    public function getAllCustomers()
    {
        $customers = Customer::with(['user', 'orders', 'reviews'])->get();
        return $customers;
    }

    public function getCustomerById($id)
    {
        $customer = Customer::with(['user', 'orders', 'reviews'])->find($id);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }
        return $customer;
    }

    public function createCustomer(array $data)
    {
        // Handle the profile picture upload if present
        if (isset($data['profile_picture']) && $data['profile_picture']->isValid()) {
            $path = $data['profile_picture']->store('customers/profile_pictures', 'public');
            $data['profile_picture'] = $path;
        }

        // Create the customer record
        return Customer::create([
            'username' => $data['username'],
            'address' => $data['address'],
            'phone_number' => $data['phone_number'] ?? null,
            'post_code' => $data['post_code'] ?? null,
            'city' => $data['city'],
            'country' => $data['country'],
            'status' => $data['status'],
            'preferred_language' => $data['preferred_language'] ?? null,
            'newsletter_subscription' => $data['newsletter_subscription'] ?? false,
            'user_id' => $data['user_id'],
            'store_manager_id' => $data['store_manager_id'],
            'profile_picture' => $data['profile_picture'] ?? null,
        ]);
    }

    public function updateCustomer($id, array $data)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }

        // Handle the profile picture upload if present
        if (isset($data['profile_picture']) && $data['profile_picture']->isValid()) {
            $path = $data['profile_picture']->store('customers/profile_pictures', 'public');
            $data['profile_picture'] = $path;
        }

        // Update the customer record
        $customer->update($data);

        return $customer;
    }

    public function deleteCustomer($id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }

        // Soft delete the customer
        $customer->delete();

        return $customer;
    }

    // Relationship Queries
    public function getCustomerOrders($customerId)
    {
        // Implementation here
    }

    public function getCustomerReviews($customerId)
    {
        // Implementation here
    }

    public function getCustomersByStoreManager($managerId)
    {
        // Implementation here
    }

    public function getCustomerWithUser($customerId)
    {
        // Implementation here
    }

    public function getCustomerWithRelations($customerId, array $relations = [])
    {
        // Implementation here
    }

    // Newsletter & Preferences
    public function getNewsletterSubscribers()
    {
        // Implementation here
    }

    public function updateNewsletterSubscription($customerId, bool $subscribe)
    {
        // Implementation here
    }

    // Loyalty & Reward
    public function getCustomerLoyaltyPoints($customerId)
    {
        // Implementation here
    }

    public function addLoyaltyPoints($customerId, int $points)
    {
        // Implementation here
    }

    public function redeemLoyaltyPoints($customerId, int $points)
    {
        // Implementation here
    }

    // Status Management
    public function suspendCustomer($customerId)
    {
        // Implementation here
    }

    public function activateCustomer($customerId)
    {
        // Implementation here
    }

    public function markAsInactive($customerId)
    {
        // Implementation here
    }

    // Soft Delete / Restore (if enabled)
    public function restoreCustomer($customerId)
    {
        // Implementation here
    }

    public function forceDeleteCustomer($customerId)
    {
        // Implementation here
    }

    // ✅ Advanced Filtering & Sorting (central method)
    public function advancedFilter(array $filters = [], array $sort = [], int $perPage = 15)
    {
        // Implementation here
    }

    // Reporting & Statistics
    public function countCustomersByStatus(string $status)
    {
        // Implementation here
    }
    public function countTotalCustomers()
    {
        // Implementation here
    }

    public function getRecentCustomers(int $limit = 5)
    {
        // Implementation here
    }
}
