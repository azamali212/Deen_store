<?php

namespace App\Http\Controllers\Permission_Settings;

use App\Data\Permissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Repositories\PermissionSettings\Role\RoleRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    protected $roleRepository;

    public function __construct(RoleRepositoryInterface $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $roles = $this->roleRepository->getRoles($request->get('per_page', 15));
        return response()->json($roles);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = $this->roleRepository->createRole($data);

        return response()->json([
            'message' => 'Role created successfully!',
            'role' => $role
        ], 201);
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
    public function attachPermissions(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'in:' . implode(',', Permissions::getAll()),  // Validate the permission ids against the predefined permissions
        ]);

        // Attach the permissions to the role
        $role = $this->roleRepository->attachPermissions([
            'role_id' => $id,
            'permission_ids' => $data['permission_ids'],
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

}
