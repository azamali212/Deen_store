<?php

namespace App\Repositories\User;

use Illuminate\Http\JsonResponse;

interface UserRepositoryInterface
{
    /**
     * Get all users with pagination, filtering, and sorting based on request parameters.
     *
     * @param $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllUsers($request);

    /**
     * Get a user by their ID, including optional related models.
     *
     * @param $id
     * @param array $relations
     * @return \App\Models\User|null
     */
    public function getUserById($id, $relations = []);

    /**
     * Create a new user with validation and notifications.
     *
     * @param array $data
     * @param bool $sendNotification
     * @return \App\Models\User
     */
    public function createUser(array $data, bool $sendNotification = true);

    /**
     * Update an existing user by their ID with complex logic handling.
     *
     * @param int $id
     * @param array $data
     * @return \App\Models\User
     */
    public function updateUser($id, $data);

    /**
     * Soft delete a user by their ID, with audit logging.
     *
     * @param int $id
     * @return bool
     */
    public function deleteUser($id);
    public function showRecycleBinUsers(): JsonResponse;

    /**
     * Restore a soft-deleted user with related entities.
     *
     * @param int $id
     * @return bool
     */
    public function restoreUser($id): JsonResponse;

    /**
     * Forcefully delete a user (permanently) with cascading deletes for related data.
     *
     * @param int $id
     * @return bool
     */
    public function forceDeleteUser($id);

    /**
     * Search users by various criteria (e.g., name, email, role), with dynamic filters.
     *
     * @param array $criteria
     * @param array $filterOptions
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchUsers(array $criteria, array $filterOptions = []);

    /**
     * Change the role of a user with validation and cascading effects.
     *
     * @param int $id
     * @param string $roleName
     * @return \App\Models\User
     */
    public function changeUserRole($id, $roleName);

    /**
     * Assign multiple roles to a user, checking for duplicates and handling cascading updates.
     *
     * @param int $id
     * @param array $roleNames
     * @return \App\Models\User
     */
    public function assignRolesToUser($id, array $roleNames);

    /**
     * Revoke a specific role from a user with logging.
     *
     * @param int $id
     * @param string $roleName
     * @return \App\Models\User
     */
    public function revokeRoleFromUser($id, $roleName);

    /**
     * Log user actions (e.g., login, profile update), using advanced event-driven logging.
     *
     * @param int $userId
     * @param string $action
     * @param string $details
     * @return bool
     */
    public function logUserAction($userId, $action, $details);

    /**
     * Activate a user (set active status), including notifications.
     *
     * @param int $id
     * @return \App\Models\User
     */
    public function activateUser($id);

    /**
     * Deactivate a user (set inactive status), with potential additional logic like session invalidation.
     *
     * @param int $id
     * @return \App\Models\User
     */
    public function deactivateUser($id);

    /**
     * Batch delete users (soft delete) based on given criteria with validation.
     *
     * @param array $userIds
     * @return int
     */
    public function batchDeleteUsers(array $userIds);

    /**
     * Batch restore users (from soft delete) with validation and optional logging.
     *
     * @param array $userIds
     * @return int
     */
    public function batchRestoreUsers(array $userIds);

    /**
     * Batch force delete users (permanent deletion) with cascading deletes and auditing.
     *
     * @param array $userIds
     * @return int
     */
    public function batchForceDeleteUsers(array $userIds);

    /**
     * Get the list of users who have not been active for a certain period (e.g., inactive for 90 days).
     *
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInactiveUsers($days);

    /**
     * Retrieve users based on advanced criteria like account age, last login, etc.
     *
     * @param array $advancedCriteria
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByAdvancedCriteria(array $advancedCriteria);

    /**
     * Get users with certain role permissions.
     *
     * @param string $permission
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersWithPermission($permission);

    /**
     * Bulk update users' statuses (e.g., activate/deactivate) with background processing.
     *
     * @param array $userIds
     * @param string $status
     * @return int
     */
    public function bulkUpdateUserStatus(array $userIds, $status);

    /**
     * Cache the results of common queries for performance optimization.
     *
     * @param string $cacheKey
     * @param callable $queryCallback
     * @param int $ttl
     * @return mixed
     */
    public function cacheQueryResult($cacheKey, callable $queryCallback, $ttl = 60);

    /**
     * Handle complex user permission assignments with caching and validation.
     *
     * @param int $userId
     * @param array $permissions
     * @return \App\Models\User
     */
    public function assignPermissionsToUser($userId, array $permissions);
}