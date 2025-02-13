<?php
namespace App\Repositories\PermissionSettings\Role;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    // Get all roles with pagination
    public function getRoles($perPage = 15)
    {
        return Role::paginate($perPage); // Retrieves paginated roles
    }
    
    // Get role by ID
    public function getRoleById($id)
    {
        return Role::findOrFail($id); // Finds the role by ID or throws an exception if not found
    }
    
    // Create a new role
    public function createRole($data)
    {
        return Role::create($data); // Create a new role from the given data
    }
    
    // Update an existing role
    public function updateRole($data, $id)
    {
        $role = Role::findOrFail($id);
        $role->update($data); // Updates the role with the given data
        return $role;
    }
    
    // Delete a role by ID
    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);
        return $role->delete(); // Deletes the role from the database
    }
    
    // Get permissions assigned to a specific role by ID with pagination
    public function getRolePermissions($id, $perPage = 15)
    {
        $role = Role::findOrFail($id);
        return $role->permissions()->paginate($perPage); // Paginated permissions for the role
    }
    
    // Attach permissions to a role
    public function attachPermissions($data)
    {
        $role = Role::findOrFail($data['role_id']);
        $permissions = Permission::find($data['permission_ids']);
        $role->permissions()->attach($permissions); // Attach the given permissions to the role
        return $role;
    }
    
    // Detach permissions from a role
    public function detachPermissions(array $data)
{
    $role = Role::findOrFail($data['role_id']); // Find the role by ID
    
    // Get permission names from IDs
    $permissions = Permission::whereIn('id', $data['permission_ids'])->pluck('name')->toArray();
    
    // Check if these permissions exist in the static Permissions list
    $validPermissions = array_intersect($permissions, \App\Data\Permissions::getAll());
    
    if (empty($validPermissions)) {
        return response()->json(['message' => 'No valid permissions found to detach.'], 422);
    }
    
    // Detach valid permissions
    $role->permissions()->detach($data['permission_ids']);
    
    // Refresh and return role with permissions
    return $role->refresh()->load('permissions');
}
    
    // Get users assigned to a specific role by ID with pagination
    public function getRoleUsers($id, $perPage = 15)
    {
        $role = Role::findOrFail($id);
        return $role->users()->paginate($perPage); // Paginated users associated with the role
    }
    
    // Attach users to a role
    public function attachUsers($data)
    {
        $role = Role::findOrFail($data['role_id']);
        $users = User::find($data['user_ids']);
        $role->users()->attach($users); // Attach the users to the role
        return $role->load('users');
    }
    
    // Detach users from a role
    public function detachUsers($data)
    {
        $role = Role::findOrFail($data['role_id']);
        $users = User::find($data['user_ids']);
        $role->users()->detach($users); // Detach the users from the role
        return $role;
    }

    // Get role by slug
    public function getRoleBySlug($slug)
    {
        return Role::where('slug', $slug)->firstOrFail(); // Retrieves the role by its slug
    }

    // Get role permissions by slug and user with pagination
    public function getRolePermissionsBySlugAndUser($slug, $user, $perPage = 15)
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        return $role->permissions()->whereHas('users', function ($query) use ($user) {
            $query->where('id', $user->id);
        })->paginate($perPage); // Paginated permissions for the role by slug and user
    }

    // Get role by slug and user
    public function getRoleBySlugAndUser($slug, $user)
    {
        return Role::where('slug', $slug)->whereHas('users', function ($query) use ($user) {
            $query->where('id', $user->id);
        })->firstOrFail(); // Retrieves the role by slug and user
    }

    // Get role by slug and user ID
    public function getRoleBySlugAndUserId($slug, $userId)
    {
        return Role::where('slug', $slug)->whereHas('users', function ($query) use ($userId) {
            $query->where('id', $userId);
        })->firstOrFail(); // Retrieves the role by slug and user ID
    }

    // Get role permissions by slug and user ID with pagination
    public function getRolePermissionsBySlugAndUserId($slug, $userId, $perPage = 15)
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        return $role->permissions()->whereHas('users', function ($query) use ($userId) {
            $query->where('id', $userId);
        })->paginate($perPage); // Paginated permissions by slug and user ID
    }

    // Get role by slug and user email
    public function getRoleBySlugAndUserEmail($slug, $email)
    {
        return Role::where('slug', $slug)->whereHas('users', function ($query) use ($email) {
            $query->where('email', $email);
        })->firstOrFail(); // Retrieves the role by slug and user email
    }

    // Get role permissions by slug and user email with pagination
    public function getRolePermissionsBySlugAndUserEmail($slug, $email, $perPage = 15)
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        return $role->permissions()->whereHas('users', function ($query) use ($email) {
            $query->where('email', $email);
        })->paginate($perPage); // Paginated permissions by slug and user email
    }
}