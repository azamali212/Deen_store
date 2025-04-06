<?php

namespace App\Repositories\User;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use App\Exceptions\UserCreationException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class UserRepository implements UserRepositoryInterface
{
    public function getAllUsers($request)
    {
        // Initialize the query builder
        $query = User::query();

        // Apply dynamic filters if provided in the request
        if ($request->has('filters')) {
            $filters = $request->input('filters');

            foreach ($filters as $key => $value) {
                if ($key === 'name') {
                    $query->where('name', 'like', '%' . $value . '%');
                } elseif ($key === 'email') {
                    $query->where('email', 'like', '%' . $value . '%');
                } elseif ($key === 'role') {
                    $query->whereHas('roles', function ($query) use ($value) {
                        $query->where('name', $value);
                    });
                } elseif ($key === 'status') {
                    $query->where('status', $value);
                } elseif ($key === 'created_at') {
                    $query->whereDate('created_at', $value);
                }
                // Add more custom filters as necessary
            }
        }

        // Apply sorting based on the request
        if ($request->has('sort')) {
            $sort = $request->input('sort');
            $sortDirection = $request->input('sort_direction', 'asc');

            $query->orderBy($sort, $sortDirection);
        }

        // Apply pagination if necessary
        $perPage = $request->input('per_page', 15); // Default 15 items per page
        $users = $query->paginate($perPage);

        // Optionally include related models
        if ($request->has('include')) {
            $includeRelations = $request->input('include');

            foreach ($includeRelations as $relation) {
                if (method_exists(User::class, $relation)) {
                    $query->with($relation);
                }
            }
        }

        // Return the paginated users with any applied filters, sorting, and relations
        return $users;
    }
    /**
     * Get a user by their ID, including optional related models.
     *
     * @param $id
     * @param array $relations
     * @return \App\Models\User|null
     */
    public function getUserById($id, $relations = [])
    {
        $requestedRelations = request()->query('relations');
        if (is_string($requestedRelations)) {
            $relations = array_map('trim', explode(',', $requestedRelations));
        }

        $user = User::with(['roles.permissions'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'permissions' => $user->getAllPermissions(),
            ]
        ]);
    }

    /**
     * Create a new user in the system.
     *
     * @param array $data
     * @param bool $sendNotification
     * @return User
     * @throws \Exception
     */
    public function createUser(array $data, bool $sendNotification = true)
    {
        DB::beginTransaction();

        try {
            // Step 1: Generate a unique email verification token for the user
            $emailVerificationToken = Str::random(60);

            // Step 2: Create the user instance
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => null,  // Email not verified yet
                'email_verification_token' => $emailVerificationToken,

            ]);

            if (isset($data['roles']) || isset($data['role'])) {
                $rolesInput = $data['roles'] ?? [$data['role']];
                $user->assignRole($rolesInput);
                Log::info('Assigned roles:', $rolesInput); // 👈 Add this for debug
            }

            // Assign permissions (single or multiple)
            if (isset($data['permissions']) || isset($data['permission'])) {
                $permissionsInput = $data['permissions'] ?? [$data['permission']];
                $user->givePermissionTo($permissionsInput);
            }

            // Step 5: Optional profile-related logic (e.g., avatar, etc.)
            if (isset($data['profile_picture'])) {
                $user->profile_picture = $data['profile_picture']; // Assuming you handle the file upload somewhere
                $user->save();
            }

            // Step 6: Send email verification if necessary
            if ($sendNotification && !$user->email_verified_at) {
                $this->sendEmailVerification($user);
            }

            // Step 7: Log user creation action (for auditing purposes)
            Log::info("User created", [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);

            // Commit the transaction after everything is successfully handled
            DB::commit();

            return $user->load(['roles', 'permissions']);
        } catch (\Exception $e) {
            DB::rollBack();  // Rollback transaction if anything goes wrong

            // Log the exception for debugging
            Log::error('Error creating user: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
            ]);

            // Throw a custom exception to handle it in the controller or service layer
            throw new \Exception("An error occurred while creating the user.", 500);
        }
    }

    protected function sendEmailVerification(User $user)
    {
        // You may want to send an actual email or use a notification system here
        //Notification::send($user, new UserCreatedNotification($user));
    }


    public function updateUser($id, $data)
    {
        DB::beginTransaction();

        try {
            // Step 1: Find the user by ID
            $user = User::findOrFail($id);

            // Step 2: Update the user attributes
            $validatedData = [
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'password' => isset($data['password']) ? Hash::make($data['password']) : $user->password,
            ];

            // Update the user details
            $user->update($validatedData);

            // Step 3: Handle roles (sync if roles are passed)
            if (isset($data['roles']) || isset($data['role'])) {
                $rolesInput = $data['roles'] ?? [$data['role']];
                $user->syncRoles($rolesInput);  // Sync roles (assign new roles, remove old ones)
            }

            // Step 4: Handle permissions (sync if permissions are passed)
            if (isset($data['permissions']) || isset($data['permission'])) {
                $permissionsInput = $data['permissions'] ?? [$data['permission']];
                $user->syncPermissions($permissionsInput);  // Sync permissions
            }

            // Step 5: Handle profile picture upload
            if (isset($data['profile_picture'])) {
                $user->profile_picture = $data['profile_picture'];  // Assuming the file path is provided
                $user->save();
            }

            // Step 6: Log the update action (for auditing purposes)
            Log::info("User updated", [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->permissions->pluck('name')->toArray(),
            ]);

            // Step 7: Commit the transaction
            DB::commit();

            return $user->load('roles', 'permissions');  // Return the updated user with roles and permissions
        } catch (Exception $e) {
            // Rollback in case of error
            DB::rollBack();

            // Log the error
            Log::error('Error updating user: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
            ]);

            throw new Exception("An error occurred while updating the user.", 500);
        }
    }

    // Step 3: Check if the user is associated with any dependencies (e.g., eCommerce store, orders, etc.)
    /*if ($user->eCommerceStore()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is associated with an eCommerce store. Please remove the association before deleting.'
                ], 400);
            }*/

    // Step 4: Check if the user has any active orders or related records
    /* if ($user->orders()->where('status', 'active')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user has active orders. Please complete or cancel the orders before deleting.'
                ], 400);
            }*/

    // Step 5: Check if the user has any ongoing product listings or other eCommerce-specific assignments
    /* if ($user->productListings()->where('status', 'active')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user has active product listings. Please remove or deactivate the listings before deleting.'
                ], 400);
            }*/
    public function deleteUser($id)
    {
        // Start a transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Step 1: Find the user by ID and load their roles
            $user = User::with('roles')->findOrFail($id);

            // Check if the user has roles
            if ($user->roles->isEmpty()) {
                // If the user has no roles, log this information
                Log::warning('User has no roles', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ]);
            }

            // If the user has restricted roles (Admin, Super Admin), prevent deletion
            if ($user->hasRole(['Admin', 'Super Admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this user due to role restrictions'
                ], 403);
            }

            // Log the deletion attempt for auditing purposes
            Log::info("User deletion initiated", [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);

            // Step 5: Delete the user
            $user->delete();

            // Commit the transaction
            DB::commit();

            // Log the successful deletion
            Log::info("User successfully deleted", [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);

            // Return success response with user details and roles
            return response()->json([
                'success' => true,
                'message' => 'User successfully deleted',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'roles' => $user->roles->pluck('name')->toArray(),  // Roles should now be included
                ]
            ], 200);
        } catch (Exception $e) {
            // Rollback in case of any error
            DB::rollBack();

            // Log the error
            Log::error('Error deleting user: ' . $e->getMessage(), [
                'user_id' => $id,
                'exception' => $e,
            ]);

            // Return a failure response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the user. Please try again later.',
            ], 500);
        }
    }

    public function showRecycleBinUsers(): JsonResponse
    {
        try {
            // Retrieve all soft-deleted users (where deleted_at is not null)
            $softDeletedUsers = User::onlyTrashed()->with('roles')->get();
    
            if ($softDeletedUsers->isEmpty()) {
                // If no soft-deleted users found, log and return a response
                Log::info('No soft-deleted users found.');
                return response()->json([
                    'success' => false,
                    'message' => 'No users found in the recycle bin.',
                ], 404);
            }
    
            // Prepare user data for the response
            $userData = $softDeletedUsers->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'deleted_at' => $user->deleted_at,
                ];
            });
    
            return response()->json([
                'success' => true,
                'message' => 'Soft-deleted users retrieved successfully.',
                'data' => $userData,
            ], 200);
        } catch (Exception $e) {
            // Log the exception details
            Log::error('Error retrieving soft-deleted users: ' . $e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching soft-deleted users.',
            ], 500);
        }
    }

    public function restoreUser($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Log the incoming ID
            Log::info("Attempting to restore user with ID: $id");

            // Find the user including soft-deleted ones
            $user = User::withTrashed()->find($id);

            if (!$user) {
                // Log the warning if user is not found
                Log::warning('User not found', ['user_id' => $id]);

                // Return 404 with proper structure
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Check if the user is already restored (not trashed)
            if (!$user->trashed()) {
                Log::info('User is not deleted, no need to restore', ['user_id' => $id]);

                // Return a 400 error if the user is not deleted
                return response()->json([
                    'success' => false,
                    'message' => 'User is not deleted',
                ], 400);
            }

            // Restore the user
            $user->restore();

            // Log the successful restoration
            Log::info('User successfully restored', [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);

            DB::commit();

            // Return a success response
            return response()->json([
                'success' => true,
                'message' => 'User restored successfully',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'roles' => $user->roles->pluck('name')->toArray(),
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            // Log the exception details
            Log::error('Exception while restoring user', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while restoring the user.',
            ], 500);
        }
    }

    public function forceDeleteUser($id): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Log the incoming ID
            Log::info("Attempting to force delete user with ID: $id");

            // Find the user including soft-deleted ones
            $user = User::withTrashed()->find($id);

            // Ensure the user exists
            if (!$user) {
                Log::warning('User not found in the database', ['user_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Log the soft-deleted status
            Log::info('User found. Soft-deleted status: ' . ($user->trashed() ? 'Yes' : 'No'), ['user_id' => $id]);

            // If the user is soft-deleted, force delete the user
            if ($user->trashed()) {
                // Proceed with force delete
                $user->forceDelete();
                Log::info('User successfully force deleted', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ]);
            } else {
                // If the user is not soft-deleted, log the status and return a response
                Log::warning('User not soft-deleted. Proceeding with regular delete', ['user_id' => $id]);

                // Regular delete if not soft-deleted
                $user->delete();
                Log::info('User successfully deleted', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User force deleted successfully',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ],
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            // Log the exception details
            Log::error('Exception while force deleting user', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while force deleting the user.',
            ], 500);
        }
    }
    public function searchUsers($criteria, $filters = [], $sort = 'name', $sortDirection = 'asc', $perPage = 15) {}

    public function changeUserRole($id, $roleName) {}
    public function assignRolesToUser($id, array $roleNames) {}
    public function revokeRoleFromUser($id, $roleName) {}
    public function logUserAction($userId, $action, $details) {}
    public function activateUser($id) {}
    public function deactivateUser($id) {}
    public function batchDeleteUsers(array $userIds) {}

    /**
     * Batch restore users (from soft delete) with validation and optional logging.
     *
     * @param array $userIds
     * @return int
     */
    public function batchRestoreUsers(array $userIds) {}

    /**
     * Batch force delete users (permanent deletion) with cascading deletes and auditing.
     *
     * @param array $userIds
     * @return int
     */
    public function batchForceDeleteUsers(array $userIds) {}
    public function getInactiveUsers($days) {}

    /**
     * Retrieve users based on advanced criteria like account age, last login, etc.
     *
     * @param array $advancedCriteria
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByAdvancedCriteria(array $advancedCriteria) {}

    /**
     * Get users with certain role permissions.
     *
     * @param string $permission
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersWithPermission($permission) {}

    /**
     * Bulk update users' statuses (e.g., activate/deactivate) with background processing.
     *
     * @param array $userIds
     * @param string $status
     * @return int
     */
    public function bulkUpdateUserStatus(array $userIds, $status) {}

    /**
     * Cache the results of common queries for performance optimization.
     *
     * @param string $cacheKey
     * @param callable $queryCallback
     * @param int $ttl
     * @return mixed
     */
    public function cacheQueryResult($cacheKey, callable $queryCallback, $ttl = 60) {}

    /**
     * Handle complex user permission assignments with caching and validation.
     *
     * @param int $userId
     * @param array $permissions
     * @return \App\Models\User
     */
    public function assignPermissionsToUser($userId, array $permissions) {}
}
