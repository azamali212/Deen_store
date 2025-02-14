<?php

namespace App\Repositories\PermissionSettings\Permission;


use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function getPermissions($perPage = 10)
    {
        return Permission::with('roles') // Eager load roles to reduce queries
            ->paginate($perPage);
    }

    public function getPermission($id)
    {
        return Permission::with(['roles', 'users'])->findOrFail($id);
    }

    public function createPermission(array $data)
    {
        return Permission::create($data);
    }

    public function updatePermission(array $data, $id)
    {
        $permission = Permission::findOrFail($id);
        $permission->update($data);
        $permission->save();
        return $permission;
    }

    public function deletePermission($id)
    {
        return Permission::destroy($id); // Uses direct deletion for better performance
    }

    public function getPermissionDetails($permissionId, $roleSlug = null, $userId = null, $email = null, $perPage = 10)
    {
        $permission = Permission::with(['roles', 'users'])->find($permissionId);
        if (!$permission) {
            return null;
        }

        // Get roles that have this permission (Paginated)
        $rolesWithPermission = Role::whereHas('permissions', function ($query) use ($permissionId) {
            $query->where('id', $permissionId);
        })->paginate($perPage);

        // Get users with this permission (Paginated)
        $usersWithPermission = User::whereHas('roles.permissions', function ($query) use ($permissionId) {
            $query->where('id', $permissionId);
        })->paginate($perPage);

        // Get permissions for a specific role if provided
        $rolePermissions = $roleSlug
            ? Role::where('slug', $roleSlug)->with('permissions')->first()
            : null;

        // Get user by ID or email (if provided)
        $user = null;
        if ($userId) {
            $user = User::with(['roles.permissions'])->find($userId);
        } elseif ($email) {
            $user = User::where('email', $email)->with(['roles.permissions'])->first();
        }

        return [
            'permission' => $permission,
            'roles_with_permission' => $rolesWithPermission,
            'users_with_permission' => $usersWithPermission,
            'role_permissions' => $rolePermissions,
            'user' => $user,
        ];
    }
}