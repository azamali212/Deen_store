<?php

namespace App\Http\Controllers\Permission_Settings;

use App\Domain\Roles\Services\RoleApprovalService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\PendingRoleRequest;
use App\Repositories\PermissionSettings\Role\RoleRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class RoleController extends Controller
{
    public function __construct(
        protected RoleRepositoryInterface $roleRepository,
        protected RoleApprovalService $roleApprovalService,
    ) {}

    /**
     * List all roles
     */
    public function index(Request $request): JsonResponse
    {
        $roles = $this->roleRepository->getRoles(
            $request->get('per_page', 15),
            $request->get('search')
        );

        return response()->json($roles);
    }

    /**
     * Create role directly if Super Admin.
     * Otherwise create pending approval request.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Super Admin -> create immediately
            if ($user->hasRole('Super Admin', 'api')) {
                $role = $this->roleRepository->createRole($validated);

                return response()->json([
                    'success' => true,
                    'message' => 'Role created successfully.',
                    'data' => $role,
                    'requires_approval' => false,
                ], 201);
            }

            // Other admin roles -> approval request
            $pending = $this->roleApprovalService->requestRole(
                $validated,
                (string) $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Role request submitted successfully. Waiting for Super Admin approval.',
                'data' => $pending,
                'requires_approval' => true,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit role request.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Super Admin -> approve pending role request
     */
    public function approveRequest(string $id, Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !$user->hasRole('Super Admin', 'api')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $role = $this->roleApprovalService->approve(
                $id,
                (string) $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Role request approved successfully.',
                'role' => $role,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve role request.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Super Admin -> reject pending role request
     */
    public function rejectRequest(string $id, Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !$user->hasRole('Super Admin', 'api')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validated = $request->validate([
                'reason' => 'required|string|min:5|max:500',
            ]);

            $pendingRequest = $this->roleApprovalService->reject(
                $id,
                (string) $user->id,
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Role request rejected successfully.',
                'request' => $pendingRequest,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject role request.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Super Admin -> list pending requests
     */
    public function getPendingRequests(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !$user->hasRole('Super Admin', 'api')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $perPage = (int) $request->get('per_page', 15);
            $search = $request->get('search');

            $query = PendingRoleRequest::query()
                ->where('status', 'pending')
                ->latest();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $requests = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $requests,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending requests.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Logged-in user -> list own role requests
     */
    public function getMyPendingRequests(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $perPage = (int) $request->get('per_page', 15);

            $requests = PendingRoleRequest::query()
                ->where('created_by', (string) $user->id)
                ->latest()
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $requests,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch your role requests.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Logged-in user -> single own request detail
     */
    public function getMyRequestDetails(string $id, Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $roleRequest = PendingRoleRequest::query()
                ->where('id', $id)
                ->where('created_by', (string) $user->id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $roleRequest,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role request details.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Super Admin -> single request detail
     */
    public function getRequestDetails(string $id, Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !$user->hasRole('Super Admin', 'api')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $roleRequest = PendingRoleRequest::query()
                ->where('id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $roleRequest,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch request details.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Show role by id
     */
    public function show($id): JsonResponse
    {
        $role = $this->roleRepository->getRoleById($id);

        return response()->json($role);
    }

    /**
     * Update role
     */
    public function update(UpdateRoleRequest $request, $id): JsonResponse
    {
        $data = $request->validated();
        $role = $this->roleRepository->updateRole($data, $id);

        return response()->json([
            'message' => 'Role updated successfully!',
            'role' => $role
        ]);
    }

    /**
     * Delete single role
     */
    public function destroy($id): JsonResponse
    {
        $this->roleRepository->deleteRole($id);

        return response()->json([
            'message' => 'Role deleted successfully!'
        ]);
    }

    /**
     * Delete multiple roles
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'integer|exists:roles,id'
        ]);

        $this->roleRepository->deleteMultipleRoles($request->role_ids);

        return response()->json([
            'message' => 'Roles deleted successfully.'
        ], 200);
    }

    /**
     * Attach permissions to role
     */
    public function attachPermissions(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'permission_names' => 'required|array',
            'permission_names.*' => 'string'
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

    /**
     * Detach permissions from role
     */
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

        $role = $this->roleRepository->getRoleById($id);

        return response()->json([
            'message' => 'Permissions detached successfully!',
            'role' => $role
        ]);
    }

    /**
     * Attach users to role
     */
    public function attachUsers(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'string|exists:users,id',
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
     * Detach users from role
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

    /**
     * Get role permissions
     */
    public function getPermissions($id, Request $request): JsonResponse
    {
        $permissions = $this->roleRepository->getRolePermissions(
            $id,
            $request->get('per_page', 15)
        );

        return response()->json($permissions);
    }

    /**
     * Get role users
     */
    public function getUsers($id, Request $request): JsonResponse
    {
        $users = $this->roleRepository->getRoleUsers(
            $id,
            $request->get('per_page', 15)
        );

        return response()->json($users);
    }

    /**
     * Get role details with related data
     */
    public function getRoleDetails($slug, Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        $email = $request->query('email');

        $userId = is_numeric($userId) ? (int) $userId : null;

        $role = $this->roleRepository->getRoleBySlug($slug);
        $permissions = $role->permissions;
        $users = $role->users;

        $roleByUser = $userId
            ? $this->roleRepository->getRoleBySlugAndUserId($slug, $userId)
            : null;

        $roleByEmail = $email
            ? $this->roleRepository->getRoleBySlugAndUserEmail($slug, $email)
            : null;

        return response()->json([
            'role' => $role,
            'permissions' => $permissions,
            'users' => $users,
            'role_by_user' => $roleByUser,
            'role_by_email' => $roleByEmail,
        ], 200);
    }
}