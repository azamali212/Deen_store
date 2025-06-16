<?php

namespace App\Repositories\PermissionSettings\Role;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleRepository implements RoleRepositoryInterface
{
    // Get all roles with pagination
    public function getRoles($perPage = 15, $searchQuery = null)
    {
        return Role::with('permissions')
            ->withCount('users') // Add this line to include user counts
            ->when($searchQuery, function ($query) use ($searchQuery) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'like', "%{$searchQuery}%")
                        ->orWhere('slug', 'like', "%{$searchQuery}%")
                        ->orWhereHas('permissions', function ($q) use ($searchQuery) {
                            $q->where('name', 'like', "%{$searchQuery}%");
                        });
                });
            })
            ->paginate($perPage);
    }
    // Get role by ID
    public function getRoleById($id)
    {
        return Role::findOrFail($id); // Finds the role by ID or throws an exception if not found
    }

    // Create a new role
    // Create a new role and optionally assign permissions
    public function createRole($data)
    {
        DB::beginTransaction();

        try {
            // Create the role
            $role = Role::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
            ]);

            // Optionally assign permissions
            if (!empty($data['permission_names']) && is_array($data['permission_names'])) {
                $permissions = Permission::whereIn('name', $data['permission_names'])->get();

                // Validate all requested permissions exist
                if ($permissions->count() !== count($data['permission_names'])) {
                    throw new \Exception('Some permissions do not exist');
                }

                $role->syncPermissions($permissions);
            }

            DB::commit();
            return $role->load('permissions');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Update an existing role
    public function updateRole($data, $id)
    {
        \Log::info('updateRole() called', ['data' => $data, 'id' => $id]);

        DB::beginTransaction();

        try {
            $role = Role::with('permissions')->findOrFail($id);
            \Log::info('Role found', ['role' => $role->id]);

            $role->update([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
            ]);

            if (isset($data['permission_names']) && is_array($data['permission_names'])) {
                \Log::info('Permission names provided', $data['permission_names']);

                $permissions = Permission::whereIn('slug', $data['permission_names'])->get();
                \Log::info('Permissions matched', $permissions->pluck('slug')->toArray());

                if ($permissions->count() !== count($data['permission_names'])) {
                    throw new \Exception('Some permissions do not exist.');
                }

                $role->syncPermissions($permissions);
                \Log::info('Permissions synced to role', ['role_id' => $role->id]);
            }

            DB::commit();

            return $role->fresh()->load('permissions');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in updateRole()', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    // Delete a role by ID
    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);
        return $role->delete(); // Deletes the role from the database
    }

    public function deleteMultipleRoles(array $roleIds): bool
    {
        // Validate input
        if (empty($roleIds)) {
            throw new \InvalidArgumentException('No role IDs provided');
        }

        DB::beginTransaction();

        try {
            $roles = Role::whereIn('id', $roleIds)->get();

            if ($roles->isEmpty()) {
                throw new ModelNotFoundException('No matching roles found for deletion.');
            }

            // Check if user has permission to delete these roles
            // Add your authorization logic here if needed

            $deletedIds = [];
            foreach ($roles as $role) {
                $deletedIds[] = $role->id;
                $role->permissions()->detach();
                $role->users()->detach();
                $role->delete();
            }

            DB::commit();



            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
        DB::beginTransaction();

        try {
            $role = Role::findOrFail($data['role_id']);

            // Get permission names from request
            $permissionNames = $data['permission_names'];

            // Find permissions by name
            $permissions = Permission::whereIn('name', $permissionNames)->get();

            // Validate all permissions exist
            if ($permissions->count() !== count($permissionNames)) {
                throw new \Exception('Some permissions do not exist');
            }

            // Sync permissions (detaches all not in array and attaches new ones)
            $role->syncPermissions($permissions);

            DB::commit();

            return $role->load('permissions');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
        $roleId = is_numeric($data['role_id']) ? (int)$data['role_id'] : $data['role_id'];
        $role = Role::findOrFail($roleId);
        $users = User::find($data['user_ids']);
        $role->users()->attach($users);
        return $role->load('users');
    }

    public function detachUsers($data)
    {
        $roleId = is_numeric($data['role_id']) ? (int)$data['role_id'] : $data['role_id'];
        $role = Role::findOrFail($roleId);
        $users = User::find($data['user_ids']);
        $role->users()->detach($users);
        return $role;
    }

    public function getRoleBySlug(string $slug): ?Role
    {
        return Role::where('slug', $slug)->with(['permissions', 'users'])->firstOrFail();
    }

    public function getRoleBySlugAndUserId(string $slug, int $userId): ?Role
    {
        return Role::where('slug', $slug)
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('id', $userId);
            })->with('permissions')->first();
    }

    public function getRoleBySlugAndUserEmail(string $slug, string $email): ?Role
    {
        return Role::where('slug', $slug)
            ->whereHas('users', function ($query) use ($email) {
                $query->where('email', $email);
            })->with('permissions')->first();
    }

    // Get role by slug
    // public function getRoleBySlug($slug)
    // {
    //     return Role::where('slug', $slug)->firstOrFail(); // Retrieves the role by its slug
    // }

    // Get role permissions by slug and user with pagination
    // public function getRolePermissionsBySlugAndUser($slug, $user, $perPage = 15)
    // {
    //     $role = Role::where('slug', $slug)->firstOrFail();
    //     return $role->permissions()->whereHas('users', function ($query) use ($user) {
    //         $query->where('id', $user->id);
    //     })->paginate($perPage); // Paginated permissions for the role by slug and user
    // }

    // Get role by slug and user
    // public function getRoleBySlugAndUser($slug, $user)
    // {
    //     return Role::where('slug', $slug)->whereHas('users', function ($query) use ($user) {
    //         $query->where('id', $user->id);
    //     })->firstOrFail(); // Retrieves the role by slug and user
    // }

    // Get role by slug and user ID
    // public function getRoleBySlugAndUserId($slug, $userId)
    // {
    //     return Role::where('slug', $slug)->whereHas('users', function ($query) use ($userId) {
    //         $query->where('id', $userId);
    //     })->firstOrFail(); // Retrieves the role by slug and user ID
    // }

    // Get role permissions by slug and user ID with pagination
    // public function getRolePermissionsBySlugAndUserId($slug, $userId, $perPage = 15)
    // {
    //     $role = Role::where('slug', $slug)->firstOrFail();
    //     return $role->permissions()->whereHas('users', function ($query) use ($userId) {
    //         $query->where('id', $userId);
    //     })->paginate($perPage); // Paginated permissions by slug and user ID
    // }

    // Get role by slug and user email
    // public function getRoleBySlugAndUserEmail($slug, $email)
    // {
    //     return Role::where('slug', $slug)->whereHas('users', function ($query) use ($email) {
    //         $query->where('email', $email);
    //     })->firstOrFail(); // Retrieves the role by slug and user email
    // }

    // Get role permissions by slug and user email with pagination
    // public function getRolePermissionsBySlugAndUserEmail($slug, $email, $perPage = 15)
    // {
    //     $role = Role::where('slug', $slug)->firstOrFail();
    //     return $role->permissions()->whereHas('users', function ($query) use ($email) {
    //         $query->where('email', $email);
    //     })->paginate($perPage); // Paginated permissions by slug and user email
    // }
}
