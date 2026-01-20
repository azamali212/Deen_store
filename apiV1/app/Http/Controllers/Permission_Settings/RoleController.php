<?php

namespace App\Http\Controllers\Permission_Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\PendingRoleRequest;
use App\Models\User;
use App\Notifications\PendingRoleApprovalNotification;
use App\Notifications\PendingRoleStatusNotification;
use App\Repositories\PermissionSettings\Role\RoleRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    protected $roleRepository;

    public function __construct(RoleRepositoryInterface $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $roles = $this->roleRepository->getRoles(
            $request->get('per_page', 15),
            $request->get('search')
        );

        return response()->json($roles);
    }

    // MODIFIED: Store method with approval system
    // MODIFIED: Store method with approval system

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Check if user is super admin
        $user = Auth::user();

        // Check if user has Super Admin role with 'api' guard
        $isSuperAdmin = $user->hasRole('Super Admin', 'api');

        // If super admin, create role directly
        if ($isSuperAdmin) {
            try {
                $role = $this->roleRepository->createRole($validated);
                return response()->json([
                    'message' => 'Role created successfully',
                    'data' => $role
                ], 201);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to create role',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        // If not super admin (but could be regular Admin), create pending request
        DB::beginTransaction();
        try {
            // Check for duplicate pending request
            $duplicate = PendingRoleRequest::where('name', $validated['name'])
                ->where('created_by', $user->id)
                ->pending()
                ->exists();

            if ($duplicate) {
                throw new \Exception('You already have a pending request for this role name.');
            }

            // Check if role already exists
            if (\Spatie\Permission\Models\Role::where('name', $validated['name'])->exists()) {
                throw new \Exception('A role with this name already exists.');
            }

            // Create pending request
            $pendingRequest = PendingRoleRequest::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'] ?? null,
                'permission_names' => $validated['permission_names'] ?? [],
                'description' => $validated['description'] ?? null,
                'created_by' => $user->id,
                'status' => 'pending'
            ]);

            // Notify creator
            $user->notify(new PendingRoleStatusNotification($pendingRequest, 'pending'));

            // Notify all super admins - use 'api' guard (same as your authentication)
            $superAdmins = User::role('Super Admin', 'api')->get(); // Changed to 'api' guard
            foreach ($superAdmins as $superAdmin) {
                $superAdmin->notify(new PendingRoleApprovalNotification($pendingRequest));
            }

            DB::commit();

            return response()->json([
                'message' => 'Role request submitted successfully. Waiting for super admin approval.',
                'data' => $pendingRequest,
                'requires_approval' => true
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to submit role request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $role = $this->roleRepository->getRoleById($id);
        return response()->json($role);
    }

    public function update(UpdateRoleRequest $request, $id): JsonResponse
    {
        $data = $request->validated();
        $role = $this->roleRepository->updateRole($data, $id);

        return response()->json([
            'message' => 'Role updated successfully!',
            'role' => $role
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->roleRepository->deleteRole($id);

        return response()->json([
            'message' => 'Role deleted successfully!'
        ]);
    }

    public function destroyMultiple(Request $request)
    {
        $data = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'integer|exists:roles,id'
        ]);

        try {
            $this->roleRepository->deleteMultipleRoles($request->role_ids);
            return response()->json(['message' => 'Roles deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function attachPermissions(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'permission_names' => 'required|array',
            'permission_names.*' => 'string' // Ensure each item is a string
        ]);

        $role = $this->roleRepository->attachPermissions([
            'role_id' => $id,
            'permission_names' => $data['permission_names'],
        ]);

        return response()->json([
            'message' => 'Permissions attached successfully!',
            'role' => $role
        ]);
    }

    public function detachPermissions(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $this->roleRepository->detachPermissions([
            'role_id' => $id,
            'permission_ids' => $data['permission_ids'],
        ]);

        // Fetch the role again from the database to ensure updated data
        $role = $this->roleRepository->getRoleById($id);

        return response()->json([
            'message' => 'Permissions detached successfully!',
            'role' => $role
        ]);
    }

    public function attachUsers(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'string|exists:users,id', // Change 'exists' to expect a string ID
        ]);

        $role = $this->roleRepository->attachUsers([
            'role_id' => $id,
            'user_ids' => $data['user_ids'],
        ]);

        return response()->json([
            'message' => 'Users attached successfully!',
            'role' => $role
        ]);
    }

    /**
     * Detach users from the role.
     */
    public function detachUsers(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $role = $this->roleRepository->detachUsers([
            'role_id' => $id,
            'user_ids' => $data['user_ids'],
        ]);

        return response()->json([
            'message' => 'Users detached successfully!',
            'role' => $role
        ]);
    }

    public function getPermissions($id, Request $request): JsonResponse
    {
        $permissions = $this->roleRepository->getRolePermissions($id, $request->get('per_page', 15));
        return response()->json($permissions);
    }

    /**
     * Display the users assigned to the role.
     */
    public function getUsers($id, Request $request): JsonResponse
    {
        $users = $this->roleRepository->getRoleUsers($id, $request->get('per_page', 15));
        return response()->json($users);
    }

    // public function getRoleBySlug($slug): JsonResponse
    // {
    //     $role = $this->roleRepository->getRoleBySlug($slug);
    //     return response()->json(['role' => $role]);
    // }
    // public function getRolePermissionsBySlugAndUser($slug, Request $request): JsonResponse
    // {
    //     $user = $request->user();
    //     $perPage = $request->get('per_page', 15);

    //     $permissions = $this->roleRepository->getRolePermissionsBySlugAndUser($slug, $user, $perPage);
    //     return response()->json($permissions);
    // }
    // public function getRoleBySlugAndUser($slug, Request $request): JsonResponse
    // {
    //     $user = $request->user();
    //     $role = $this->roleRepository->getRoleBySlugAndUser($slug, $user);

    //     return response()->json(['role' => $role]);
    // }

    // public function getRoleBySlugAndUserId($slug, $userId): JsonResponse
    // {
    //     $role = $this->roleRepository->getRoleBySlugAndUserId($slug, $userId);
    //     return response()->json(['role' => $role]);
    // }

    // public function getRolePermissionsBySlugAndUserId($slug, $userId, Request $request): JsonResponse
    // {
    //     $perPage = $request->get('per_page', 15);
    //     $permissions = $this->roleRepository->getRolePermissionsBySlugAndUserId($slug, $userId, $perPage);

    //     return response()->json($permissions);
    // }

    // public function getRoleBySlugAndUserEmail($slug, $email): JsonResponse
    // {
    //     $role = $this->roleRepository->getRoleBySlugAndUserEmail($slug, $email);
    //     return response()->json(['role' => $role]);
    // }

    // public function getRolePermissionsBySlugAndUserEmail($slug, $email, Request $request): JsonResponse
    // {
    //     $perPage = $request->get('per_page', 15);
    //     $permissions = $this->roleRepository->getRolePermissionsBySlugAndUserEmail($slug, $email, $perPage);

    //     return response()->json($permissions);
    // }

    //Method Get Role Details Optimize way to get all details of roles
    public function getRoleDetails($slug, Request $request)
    {
        try {
            $userId = $request->query('user_id');
            $email = $request->query('email');

            // Convert user_id to integer only if it's numeric
            $userId = is_numeric($userId) ? (int) $userId : null;

            $role = $this->roleRepository->getRoleBySlug($slug);
            $permissions = $role->permissions;
            $users = $role->users;

            // Query role based on user_id or email if provided
            $roleByUser = $userId ? $this->roleRepository->getRoleBySlugAndUserId($slug, $userId) : null;
            $roleByEmail = $email ? $this->roleRepository->getRoleBySlugAndUserEmail($slug, $email) : null;

            return response()->json([
                'role' => $role,
                'permissions' => $permissions,
                'users' => $users,
                'role_by_user' => $roleByUser,
                'role_by_email' => $roleByEmail,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    // NEW: Get all pending role requests (for super admins)
    // NEW: Get all pending role requests (for super admins)
    public function getPendingRequests(Request $request): JsonResponse
    {
        $user = Auth::user();
        // Use 'api' guard consistently
        if (!$user->hasRole('Super Admin', 'api')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $requests = PendingRoleRequest::pending()
            ->with('creator')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('creator', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $requests
        ]);
    }

    // NEW: Get my pending requests
    // NEW: Get my pending requests
    public function getMyPendingRequests(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);

        // FIX: Use $user->id instead of $user->id()
        $requests = PendingRoleRequest::where('created_by', $user->id)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $requests
        ]);
    }

    // NEW: Approve pending request
    // NEW: Approve pending request
    public function approveRequest($ulid): JsonResponse  // This parameter name should match route
    {
        $user = Auth::user();
        // Use 'api' guard consistently
        if (!$user->hasRole('Super Admin', 'api')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            // The parameter is named $ulid but contains the ID value
            $pendingRequest = PendingRoleRequest::where('id', $ulid)->firstOrFail();

            // Or if you're using ULID as primary key, uncomment this:
            // $pendingRequest = PendingRoleRequest::where('id', $ulid)->firstOrFail();

            if (!$pendingRequest->isPending()) {
                throw new \Exception('Request is not in pending status.');
            }

            // Create actual role using your existing repository
            $roleData = [
                'name' => $pendingRequest->name,
                'slug' => $pendingRequest->slug,
                'permission_names' => $pendingRequest->permission_names ?? []
            ];

            $role = $this->roleRepository->createRole($roleData);

            // Update request status
            $pendingRequest->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now()
            ]);

            // Notify creator
            if ($pendingRequest->creator) {
                $pendingRequest->creator->notify(new PendingRoleStatusNotification($pendingRequest, 'approved', $user));
            }

            DB::commit();

            return response()->json([
                'message' => 'Role request approved successfully. Role has been created.',
                'role' => $role,
                'request' => $pendingRequest
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to approve request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // NEW: Reject pending request
    // NEW: Reject pending request
    public function rejectRequest(Request $request, $ulid): JsonResponse
    {
        $user = Auth::user();
        // Use 'api' guard consistently
        if (!$user->hasRole('Super Admin', 'api')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500'
        ]);

        DB::beginTransaction();
        try {
            // The parameter is named $ulid but contains the ID value
            $pendingRequest = PendingRoleRequest::where('id', $ulid)->firstOrFail();

            if (!$pendingRequest->isPending()) {
                throw new \Exception('Request is not in pending status.');
            }

            // Update request status
            $pendingRequest->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'rejection_reason' => $validated['reason'],
                'rejected_at' => now()
            ]);

            // Notify creator
            if ($pendingRequest->creator) {
                $pendingRequest->creator->notify(new PendingRoleStatusNotification($pendingRequest, 'rejected', $user));
            }

            DB::commit();

            return response()->json([
                'message' => 'Role request rejected successfully',
                'request' => $pendingRequest
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to reject request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // NEW: Get request statistics
    public function getRequestStats(): JsonResponse
    {
        $stats = [
            'total' => PendingRoleRequest::count(),
            'pending' => PendingRoleRequest::pending()->count(),
            'approved' => PendingRoleRequest::approved()->count(),
            'rejected' => PendingRoleRequest::rejected()->count(),
            'today_pending' => PendingRoleRequest::pending()
                ->whereDate('created_at', today())
                ->count()
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}
