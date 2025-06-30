<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\LogUserActionJob;
use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected $userRepository;
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAllUsers(Request $request)
    {
        $users = $this->userRepository->getAllUsers($request);

        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $inactiveUsers = User::where('status', 'inactive')->count();

        // Get all roles except 'customer'
        $adminRoles = Role::where('name', '!=', 'customer')->pluck('name');

        // Count all users that have any role except 'customer'
        $adminUsers = User::whereHas('roles', function ($query) use ($adminRoles) {
            $query->whereIn('name', $adminRoles);
        })->count();

        // Count users that only have 'customer' role
        $customerUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'customer');
        })->count();

        return response()->json([
            'success' => true,
            'data' => $users,
            'meta' => [
                'total_users'     => $totalUsers,
                'active_users'    => $activeUsers,
                'inactive_users'  => $inactiveUsers,
                'admin_users'     => $adminUsers,
                'customer_users'  => $customerUsers,
            ]
        ]);
    }
    public function show($id, Request $request)
    {
        // Extract 'relations' from the query string if provided
        $relations = $request->input('relations', []);

        // Call the repository method to fetch the user by ID with the given relations
        $user = $this->userRepository->getUserById($id, $relations);

        // If user is not found, return 404
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Return the user data
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function createUser(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'sometimes|string|exists:roles,name', // Optional role
            'roles' => 'sometimes|array',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|exists:permissions,name',
            'roles.*' => 'string|exists:roles,name'
        ]);

        $user = $this->userRepository->createUser($validatedData);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function updateUser($id, Request $request)
    {
        // Step 1: Validate the incoming data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'status' => 'nullable|string|in:active,inactive',  // Optional status
            'password' => 'nullable|string|min:8',
            'roles' => 'nullable|array',  // Ensure roles are an array if provided
            'roles.*' => 'exists:roles,name',  // Validate each role name exists in the roles table
            'permissions' => 'nullable|array',  // Ensure permissions are an array if provided
            'permissions.*' => 'exists:permissions,name',  // Validate each permission exists
            'profile_picture' => 'nullable|string',  // Assuming a file path or URL for the profile picture
        ]);

        // Step 2: Prepare data to pass to the repository method
        $data = $validated;

        // Step 3: Call the repository method to update the user
        try {
            $user = $this->userRepository->updateUser($id, $data);

            // Step 4: Return the updated user with roles and permissions
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user,
            ]);
        } catch (Exception $e) {
            // Handle exception if any
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function deleteUser($id)
    {
        $user = $this->userRepository->deleteUser($id);
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
            //"roles" => $user->roles,
            'data' => $user
        ]);
    }

    public function recycleBinUsers()
    {
        try {
            // Call the showRecycleBinUsers method from the repository
            $response = $this->userRepository->showRecycleBinUsers();

            // If the repository returns an error, it will already contain the proper status and message
            return $response;
        } catch (Exception $e) {
            // Log the exception details
            Log::error('Error fetching recycle bin users', [
                'exception' => $e->getMessage(),
            ]);

            // Return a generic error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching recycle bin users.',
            ], 500);
        }
    }
    public function restoreUser($id)
    {
        try {
            // Call the restoreUser method from the repository
            $response = $this->userRepository->restoreUser($id);

            // If the repository returns an error, it will already contain the proper status and message
            return $response;
        } catch (Exception $e) {
            // Log the exception details
            Log::error('Error restoring user', [
                'user_id' => $id,
                'exception' => $e->getMessage(),
            ]);

            // Return a generic error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while restoring the user.',
            ], 500);
        }
    }
    public function forceDeleteUser($id)
    {
        $user = $this->userRepository->forceDeleteUser($id);
        return response()->json([
            'success' => true,
            'message' => 'User permanently deleted successfully',
            'data' => $user
        ]);
    }

    public function searchUsers(Request $request)
    {
        $criteria = $request->input('search');
        $filters = [
            'role' => $request->input('role'),
            'status' => $request->input('status'),
            'trashed' => $request->boolean('trashed'),
            'hospital_id' => $request->input('hospital_id'),
        ];
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        $perPage = $request->input('per_page', 15);

        $users = $this->userRepository->searchUsers($criteria, $filters, $sort, $direction, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Users fetched successfully.',
            'data' => $users,
        ]);
    }

    public function changeUserRole(Request $request, $id)
    {
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = $this->userRepository->changeUserRole($id, $validated['role']);

        return response()->json([
            'success' => true,
            'message' => 'User role changed successfully.',
            'data' => $user,
        ]);
    }

    public function logUserAction(Request $request): JsonResponse
    {
        // Get data from the request
        $userId = $request->input('userId');
        $action = $request->input('action');
        $details = $request->input('details', []);

        // Dispatch the job to log the user action asynchronously
        LogUserActionJob::dispatch($userId, $action, $details);

        return response()->json([
            'message' => 'User action logged successfully.'
        ], 200);
    }

    public function userActive(string $id)
    {
        try {
            $activated = $this->userRepository->activateUser($id);

            if (!$activated) {
                return response()->json([
                    'message' => 'User already active.',
                ], 200);
            }

            return response()->json([
                'message' => 'User activated successfully.',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function userInActive(string $id)
    {
        try {
            $deactivated = $this->userRepository->deactivateUser($id);

            if (!$deactivated) {
                return response()->json([
                    'message' => 'User already inactive.',
                ], 200);
            }

            return response()->json([
                'message' => 'User deactivated successfully.',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function batchDeleteUsers(Request $request): JsonResponse
    {
        // Validate user IDs from the request
        $userIds = $request->input('userIds', []);

        if (empty($userIds)) {
            return response()->json([
                'message' => 'No user IDs provided.',
            ], 400);  // Return 400 if no IDs are provided
        }

        // Call the repository method to perform batch deletion
        $deletedCount = $this->userRepository->batchDeleteUsers($userIds);

        // Check if any users were deleted
        if ($deletedCount === 0) {
            return response()->json([
                'message' => 'No users found for the provided IDs, or restricted roles prevent deletion.',
            ], 404);  // Return 404 if no users were deleted
        }

        return response()->json([
            'message' => "$deletedCount users deleted successfully.",
            'deleted_count' => $deletedCount,
        ], 200);
    }

    public function batchRestoreUsers(Request $request): JsonResponse
    {
        // Validate user IDs from the request
        $userIds = $request->input('userIds', []);

        if (empty($userIds)) {
            return response()->json([
                'message' => 'No user IDs provided.',
            ], 400);  // Return 400 if no IDs are provided
        }

        // Call the repository method to perform batch restoration
        $restoredCount = $this->userRepository->batchRestoreUsers($userIds);

        // Check if any users were restored
        if ($restoredCount === 0) {
            // Check if any users exist in the database but were not soft deleted
            $existingUsers = User::whereIn('id', $userIds)->get();
            $nonTrashedIds = $existingUsers->pluck('id')->toArray();

            return response()->json([
                'message' => 'No users found for the provided IDs, or they could not be restored. ' .
                    (empty($nonTrashedIds) ? '' : 'Some users were not soft-deleted.'),
                'non_trashed_ids' => $nonTrashedIds,
            ], 404);  // Return 404 if no users were restored
        }

        return response()->json([
            'message' => "$restoredCount users restored successfully.",
            'restored_count' => $restoredCount,
        ], 200);
    }

    public function betchForceDeleteUsers(Request $request): JsonResponse
    {
        // Validate user IDs from the request
        $userIds = $request->input('userIds', []);

        if (empty($userIds)) {
            return response()->json([
                'message' => 'No user IDs provided.',
            ], 400);  // Return 400 if no IDs are provided
        }

        // Call the repository method to perform batch force deletion
        $deletedCount = $this->userRepository->batchForceDeleteUsers($userIds);

        // Check if any users were deleted
        if ($deletedCount === 0) {
            return response()->json([
                'message' => 'No users found for the provided IDs, or restricted roles prevent deletion.',
            ], 404);  // Return 404 if no users were deleted
        }

        return response()->json([
            'message' => "$deletedCount users permanently deleted successfully.",
            'deleted_count' => $deletedCount,
        ], 200);
    }

    public function getInactiveUsers(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);  // Default to 30 days if not provided

        try {
            $inactiveUsers = $this->userRepository->getInactiveUsers($days);

            return response()->json([
                'message' => 'Inactive users retrieved successfully.',
                'data' => $inactiveUsers
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching inactive users: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve inactive users.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users based on advanced criteria.
     */
    public function getUsersByAdvancedCriteria(Request $request): JsonResponse
    {
        $advancedCriteria = $request->input('criteria', []);

        try {
            $users = $this->userRepository->getUsersByAdvancedCriteria($advancedCriteria);

            return response()->json([
                'message' => 'Users retrieved successfully.',
                'data' => $users
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching users by advanced criteria: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve users.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users with a specific permission.
     */
    public function getUsersWithPermission(Request $request): JsonResponse
    {
        $permission = $request->input('permission');

        if (!$permission) {
            return response()->json([
                'message' => 'Permission parameter is required.'
            ], 400);
        }

        try {
            $users = $this->userRepository->getUsersWithPermission($permission);

            return response()->json([
                'message' => 'Users with permission retrieved successfully.',
                'data' => $users
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching users with permission: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve users with permission.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update user status.
     */
    public function bulkUpdateUserStatus(Request $request): JsonResponse
    {
        $userIds = $request->input('userIds', []);
        $status = $request->input('status');

        if (empty($userIds) || !$status) {
            return response()->json([
                'message' => 'User IDs and status are required.'
            ], 400);
        }

        try {
            $updatedCount = $this->userRepository->bulkUpdateUserStatus($userIds, $status);

            return response()->json([
                'message' => "$updatedCount users updated successfully.",
                'updated_count' => $updatedCount
            ], 200);
        } catch (Exception $e) {
            Log::error('Error bulk updating user status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update user status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cache a query result to improve performance.
     */
    public function cacheQueryResult(Request $request): JsonResponse
    {
        $cacheKey = $request->input('cacheKey');
        $queryCallback = $request->input('queryCallback');

        if (!$cacheKey || !$queryCallback) {
            return response()->json([
                'message' => 'Cache key and query callback are required.'
            ], 400);
        }

        try {
            $result = $this->userRepository->cacheQueryResult($cacheKey, $queryCallback);

            return response()->json([
                'message' => 'Query result cached successfully.',
                'data' => $result
            ], 200);
        } catch (Exception $e) {
            Log::error('Error caching query result: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to cache query result.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign permissions to a user.
     */
    public function assignPermissionsToUser(Request $request): JsonResponse
    {
        $userId = $request->input('userId');
        $permissions = $request->input('permissions', []);

        if (!$userId || empty($permissions)) {
            return response()->json([
                'message' => 'User ID and permissions are required.'
            ], 400);
        }

        try {
            $user = $this->userRepository->assignPermissionsToUser($userId, $permissions);

            return response()->json([
                'message' => 'Permissions assigned successfully.',
                'data' => $user
            ], 200);
        } catch (Exception $e) {
            Log::error('Error assigning permissions to user: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to assign permissions.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
