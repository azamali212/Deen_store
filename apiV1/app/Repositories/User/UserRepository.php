<?php

namespace App\Repositories\User;

use App\Jobs\LogUserActionJob;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use App\Models\UserLogAction;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Get all users with optional filters, sorting, and pagination.
     *
     * @param $request
     * @return LengthAwarePaginator
     */
    public function getAllUsers($request): LengthAwarePaginator
    {
        $query = User::query();

        // Apply filters
        if ($request->has('filters')) {
            $filters = $request->input('filters');
            foreach ($filters as $key => $value) {
                if ($key === 'name') {
                    $query->where('name', 'like', '%' . $value . '%');
                } elseif ($key === 'email') {
                    $query->where('email', 'like', '%' . $value . '%');
                } elseif ($key === 'role') {
                    $query->whereHas('roles', fn($q) => $q->where('name', $value));
                } elseif ($key === 'status') {
                    $query->where('status', $value);
                } elseif ($key === 'created_at') {
                    $query->whereDate('created_at', $value);
                }
            }
        }

        // Sorting
        if ($request->has('sort')) {
            $query->orderBy(
                $request->input('sort'),
                $request->input('sort_direction', 'asc')
            );
        }

        // Always include roles and both types of permissions
        $query->with(['roles.permissions']);

        return $query->paginate($request->input('per_page', 15));
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
        } catch (Exception $e) {
            DB::rollBack();  // Rollback transaction if anything goes wrong

            // Log the exception for debugging
            Log::error('Error creating user: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
            ]);

            // Throw a custom exception to handle it in the controller or service layer
            throw new Exception("An error occurred while creating the user.", 500);
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
                'status' => $data['status'] ?? $user->status,
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
    /**
     * Bulk delete multiple users with validation
     * 
     * @param array $userIds
     * @return array
     */
    /**
     * Bulk delete multiple soft-deleted users (move to recycle bin)
     * 
     * @param array $userIds
     * @return array
     */
    public function bulkDeleteSoftDeletedUsers(array $userIds): array
    {
        DB::beginTransaction();

        try {
            // Get only soft-deleted users with their roles
            $users = User::onlyTrashed()->with('roles')->whereIn('id', $userIds)->get();

            if ($users->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No soft-deleted users found with the provided IDs',
                    'deleted_count' => 0
                ];
            }

            $deletedCount = 0;
            $failedIds = [];

            foreach ($users as $user) {
                try {
                    // Permanently delete the soft-deleted user
                    $user->forceDelete();
                    $deletedCount++;

                    Log::info("User permanently deleted from recycle bin", [
                        'user_id' => $user->id,
                        'user_name' => $user->name
                    ]);
                } catch (Exception $e) {
                    $failedIds[] = $user->id;
                    Log::error("Error permanently deleting user {$user->id}: " . $e->getMessage());
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Permanently deleted {$deletedCount} of " . count($userIds) . " users from recycle bin",
                'deleted_count' => $deletedCount,
                'failed_ids' => $failedIds
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete from recycle bin error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to bulk delete users from recycle bin',
                'deleted_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Restore all soft-deleted users from recycle bin
     * 
     * @return array
     */
    public function restoreAllUsers(): array
    {
        DB::beginTransaction();

        try {
            $deletedUsers = User::onlyTrashed()->get();

            if ($deletedUsers->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No deleted users found in recycle bin to restore',
                    'restored_count' => 0
                ];
            }

            $restoredCount = 0;
            $failedIds = [];

            foreach ($deletedUsers as $user) {
                try {
                    $user->restore();
                    $restoredCount++;

                    Log::info("User restored from recycle bin", [
                        'user_id' => $user->id,
                        'user_name' => $user->name
                    ]);
                } catch (Exception $e) {
                    $failedIds[] = $user->id;
                    Log::error("Error restoring user {$user->id}: " . $e->getMessage());
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Restored {$restoredCount} users from recycle bin",
                'restored_count' => $restoredCount,
                'failed_ids' => $failedIds
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Restore all from recycle bin error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to restore users from recycle bin',
                'restored_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    public function searchUsers($criteria, $filters = [], $sort = 'name', $sortDirection = 'asc', $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()->with('roles');

        // Search logic
        if (!empty($criteria)) {
            $query->where(function ($q) use ($criteria) {
                $q->where('name', 'like', '%' . $criteria . '%')
                    ->orWhere('email', 'like', '%' . $criteria . '%')
                    ->orWhere('phone_no', 'like', '%' . $criteria . '%')
                    ->orWhere('id', 'like', '%' . $criteria . '%');
            });
        }

        // Filtering by role (make sure to match exactly)
        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->whereRaw('LOWER(name) = ?', [strtolower($filters['role'])]);
            });
        }

        // Filtering by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtering by soft deleted records
        if (!empty($filters['trashed']) && $filters['trashed'] === true) {
            $query->onlyTrashed();
        }

        // Sorting
        if (in_array($sort, ['name', 'email', 'created_at', 'updated_at'])) {
            $query->orderBy($sort, $sortDirection);
        } else {
            $query->orderBy('name', 'asc');
        }

        return $query->paginate($perPage);
    }

    public function changeUserRole($id, $roleName)
    {
        // Find the user by ID
        $user = User::findOrFail($id);

        // Check if the role exists
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        // Change the user's role
        $user->syncRoles([$roleName]);

        // Log the role change
        Log::info("User role changed", [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'new_role' => $roleName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User role changed successfully',
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'new_role' => $roleName,
            ]
        ]);
    }
    public function logUserAction($userId, $action, array $details = []): void
    {
        LogUserActionJob::dispatch($userId, $action, $details);
    } // Ensure this is at the top

    public function performLog($userId, $action, array $details = []): UserLogAction
    {
        $request = request();

        $ip = $request->ip();
        $geo = $this->getGeoFromIp($ip);

        $data = [
            'user_id'       => $userId,
            'action'        => $action,
            'event_type'    => $details['event_type'] ?? null,
            'status'        => $details['status'] ?? 'success',
            'details'       => $details['details'] ?? null,
            'ip_address'    => $ip,
            'user_agent'    => $request->userAgent(),
            'device_type'   => $details['device_type'] ?? $this->getDeviceType($request),
            'device_model'  => $details['device_model'] ?? null,
            'platform'      => $details['platform'] ?? php_uname('s'),
            'browser'       => $details['browser'] ?? $this->getBrowser($request->userAgent()),
            'location'      => $geo['location'] ?? null,
            'latitude'      => $geo['latitude'] ?? null,
            'longitude'     => $geo['longitude'] ?? null,
            'route_name'    => $request->route()?->getName(),
            'url'           => $request->fullUrl(),
            'performed_by'  => Auth::check() ? Auth::user()->id : $details['performed_by'] ?? null,
        ];

        if (!empty($details['reference']) && is_object($details['reference'])) {
            $data['reference_type'] = get_class($details['reference']);
            $data['reference_id']   = $details['reference']->getKey();
        }

        return UserLogAction::create($data);
    }

    public function getGeoFromIp(string $ip): array
    {
        try {
            $response = Http::get("https://ipinfo.io/{$ip}/json");

            if ($response->successful()) {
                $info = $response->json();
                $coords = explode(',', $info['loc'] ?? '');

                return [
                    'location' => is_array($info) ? $info['city'] . ', ' . $info['country'] : null,
                    'latitude' => $coords[0] ?? null,
                    'longitude' => $coords[1] ?? null,
                ];
            }
        } catch (Exception $e) {
            // You can log the exception or ignore
        }

        return [];
    }

    public function getDeviceType($request): ?string
    {
        $agent = $request->header('User-Agent');
        if (!$agent) return null;

        // Loop through the array and check if any device type is present in the user-agent
        $mobileDevices = ['Mobile', 'Android', 'iPhone'];
        $tabletDevices = ['iPad', 'Tablet'];

        foreach ($mobileDevices as $device) {
            if (str_contains($agent, $device)) {
                return 'mobile';
            }
        }

        foreach ($tabletDevices as $device) {
            if (str_contains($agent, $device)) {
                return 'tablet';
            }
        }

        return 'desktop';
    }

    public function getBrowser($userAgent): ?string
    {
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Safari') && !str_contains($userAgent, 'Chrome')) return 'Safari';
        if (str_contains($userAgent, 'Edge')) return 'Edge';
        if (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR')) return 'Opera';
        if (str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident')) return 'Internet Explorer';

        return 'Unknown';
    }


    public function activateUser(string $id): bool
    {
        try {
            $user = User::findOrFail($id);

            if ($user->status === 'active') {
                Log::info("User [$id] is already active.");
                return false;
            }

            $user->status = 'active';
            $user->email_verified_at = now(); // Set email_verified_at to the current time
            $user->last_login_at = now(); // Set last_login_at to the current time
            $user->save(); // Save the changes

            Log::info("User [$id] activated successfully.");

            return true;
        } catch (ModelNotFoundException $e) {
            Log::error("User with ID [$id] not found.");
            throw new Exception("User not found.", 404);
        } catch (Exception $e) {
            Log::error("Error activating user [$id]: " . $e->getMessage());
            throw new Exception("Failed to activate user.", 500);
        }
    }
    public function deactivateUser(string $id): bool
    {
        try {
            $user = User::findOrFail($id);

            if ($user->status === 'inactive') {
                Log::info("User [$id] is already inactive.");
                return false;
            }

            $user->status = 'inactive';
            $user->save(); // Save the changes

            Log::info("User [$id] deactivated successfully.");

            return true;
        } catch (ModelNotFoundException $e) {
            Log::error("User with ID [$id] not found.");
            throw new Exception("User not found.", 404);
        } catch (Exception $e) {
            Log::error("Error deactivating user [$id]: " . $e->getMessage());
            throw new Exception("Failed to deactivate user.", 500);
        }
    }
    // Repository Method
    // Repository
    public function batchDeleteUsers(array $userIds)
    {
        DB::beginTransaction();

        try {
            // Validate user IDs
            $users = User::whereIn('id', $userIds)->get();
            if ($users->isEmpty()) {
                return 0;  // Return 0 if no users were found
            }

            // Check if any of the users have restricted roles
            foreach ($users as $user) {
                if ($user->hasRole(['Admin', 'Super Admin'])) {
                    return 0;  // Return 0 if any user has restricted roles
                }
            }

            // Soft delete users and return the count of deleted users
            $deletedCount = User::destroy($userIds);

            DB::commit();

            // Return the count of deleted users
            return $deletedCount;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting users: ' . $e->getMessage());
            return 0;  // Return 0 in case of an error
        }
    }

    /**
     * Batch restore users (from soft delete) with validation and optional logging.
     *
     * @param array $userIds
     * @return int
     */
    public function batchRestoreUsers(array $userIds)
    {
        DB::beginTransaction();

        try {
            Log::info('Attempting to restore users with IDs: ', $userIds);  // Log the user IDs

            // Get the trashed users using onlyTrashed
            $users = User::onlyTrashed()->whereIn('id', $userIds)->get();

            // If no trashed users are found, return 0
            if ($users->isEmpty()) {
                Log::info('No users found with the provided IDs in the soft deleted state.');
                return 0;  // Return 0 if no users were found
            }

            // Restore users and count how many were restored
            $restoredCount = 0;
            foreach ($users as $user) {
                if ($user->restore()) {
                    $restoredCount++;
                }
            }

            DB::commit();

            // Return the count of restored users
            return $restoredCount;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error restoring users: ' . $e->getMessage());
            return 0;  // Return 0 in case of an error
        }
    }
    /**
     * Batch force delete users (permanent deletion) with cascading deletes and auditing.
     *
     * @param array $userIds
     * @return int
     */
    public function batchForceDeleteUsers(array $userIds)
    {
        DB::beginTransaction();

        try {
            // Validate user IDs
            $users = User::withTrashed()->whereIn('id', $userIds)->get();
            if ($users->isEmpty()) {
                return 0;  // Return 0 if no users were found
            }

            // Check if any of the users have restricted roles
            foreach ($users as $user) {
                if ($user->hasRole(['Admin', 'Super Admin'])) {
                    return 0;  // Return 0 if any user has restricted roles
                }
            }

            // Force delete users and return the count of deleted users
            $deletedCount = User::onlyTrashed()->whereIn('id', $userIds)->forceDelete();

            DB::commit();

            // Return the count of deleted users
            return $deletedCount;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error force deleting users: ' . $e->getMessage());
            return 0;  // Return 0 in case of an error
        }
    }
    public function getInactiveUsers($days)
    {
        try {
            // Calculate the date before $days from today
            $inactiveSince = now()->subDays($days);

            // Retrieve users who have been soft deleted before $days ago
            return User::onlyTrashed()
                ->where('deleted_at', '<', $inactiveSince)
                ->orderBy('deleted_at', 'desc') // Sorting to prioritize older deleted users
                ->get();
        } catch (Exception $e) {
            Log::error('Error fetching inactive users: ' . $e->getMessage());
            return collect(); // Return an empty collection in case of an error
        }
    }

    /**
     * Retrieve users based on advanced criteria like account age, last login, etc.
     *
     * @param array $advancedCriteria
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByAdvancedCriteria(array $advancedCriteria)
    {
        try {
            $query = User::query(); // Start with the base User model

            // Loop through criteria and build the query dynamically
            foreach ($advancedCriteria as $key => $value) {
                if ($key === 'account_type') {
                    $query->where('created_at', '<', now()->subDays($value)); // Filter by account age
                }
                if ($key === 'last_login_at') {
                    $query->where('last_login_at', '>=', now()->subDays($value)); // Filter by last login
                }
                if ($key === 'role') {
                    $query->whereHas('roles', function ($query) use ($value) {
                        $query->where('name', $value); // Filter by role name
                    });
                }
                // Add additional filters as necessary
            }

            return $query->get(); // Execute and return the result
        } catch (Exception $e) {
            Log::error('Error fetching users by advanced criteria: ' . $e->getMessage());
            return collect(); // Return an empty collection in case of an error
        }
    }

    /**
     * Get users with certain role permissions.
     *
     * @param string $permission
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersWithPermission($permission)
    {
        try {
            return User::whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission); // Filter by permission name
            })
                ->with('permissions') // Eager load permissions for each user to avoid N+1 query problem
                ->get();
        } catch (Exception $e) {
            Log::error('Error fetching users with permission: ' . $e->getMessage());
            return collect(); // Return an empty collection in case of an error
        }
    }

    /**
     * Bulk update users' statuses (e.g., activate/deactivate) with background processing.
     *
     * @param array $userIds
     * @param string $status
     * @return int
     */
    public function bulkUpdateUserStatus(array $userIds, $status)
    {
        try {
            // Start transaction to ensure data consistency
            DB::beginTransaction();

            // Bulk update user status
            $updatedCount = User::whereIn('id', $userIds)
                ->update(['status' => $status]);

            // Commit the transaction
            DB::commit();

            return $updatedCount; // Return the number of users updated
        } catch (Exception $e) {
            // Rollback transaction in case of an error
            DB::rollBack();
            Log::error('Error bulk updating user statuses: ' . $e->getMessage());
            return 0; // Return 0 if no updates were made
        }
    }

    /**
     * Cache the results of common queries for performance optimization.
     *
     * @param string $cacheKey
     * @param callable $queryCallback
     * @param int $ttl
     * @return mixed
     */
    public function cacheQueryResult($cacheKey, callable $queryCallback, $ttl = 60)
    {
        try {
            // Try to fetch the data from the cache
            return Cache::remember($cacheKey, $ttl, function () use ($queryCallback) {
                return $queryCallback(); // Execute the query and return the result
            });
        } catch (Exception $e) {
            Log::error('Error caching query result: ' . $e->getMessage());
            return null; // Return null in case of an error
        }
    }

    /**
     * Handle complex user permission assignments with caching and validation.
     *
     * @param int $userId
     * @param array $permissions
     * @return \App\Models\User
     */
    public function assignPermissionsToUser($userId, array $permissions)
    {
        try {
            // Begin a database transaction to ensure data consistency
            DB::beginTransaction();

            // Fetch the user from the database
            $user = User::findOrFail($userId);

            // Validate and assign the permissions
            $validPermissions = Permission::whereIn('name', $permissions)->get();
            if ($validPermissions->count() !== count($permissions)) {
                throw new Exception('One or more permissions are invalid.');
            }

            // Sync the permissions for the user
            $user->permissions()->sync($validPermissions->pluck('id'));

            // Commit the transaction
            DB::commit();

            // Cache the updated permissions for future use
            Cache::forget("user_permissions_{$userId}");
            Cache::put("user_permissions_{$userId}", $validPermissions);

            return $user; // Return the updated user
        } catch (Exception $e) {
            DB::rollBack(); // Rollback if any error occurs
            Log::error('Error assigning permissions to user: ' . $e->getMessage());
            return null; // Return null in case of an error
        }
    }
}
