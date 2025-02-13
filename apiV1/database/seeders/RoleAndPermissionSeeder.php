<?php
namespace Database\Seeders;

use App\Data\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        ini_set('memory_limit', '2048M');
        Schema::disableForeignKeyConstraints();
        Role::truncate();
        Permission::truncate();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions from App/Data
        $permissions = Permissions::getAll();

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'slug' => Str::slug($permission), // Generate slug for permission
                'guard_name' => 'api',
            ]);
        }

        // Load roles and permissions from config/roles.php
        $rolesConfig = config('roles');

        // Create roles and assign permissions
        $roleInstances = [];
        foreach ($rolesConfig as $roleName => $roleData) {
            $guard = $roleData['guard_name'] ?? 'api'; // Default to 'api' if not specified
            $slug = Str::slug($roleName); // Generate slug for role

            $permissions = $roleData['permissions'] === 'all'
                ? Permission::all()
                : Permission::whereIn('name', $roleData['permissions'])->get();

            $role = Role::firstOrCreate(
                ['name' => $roleName, 'slug' => $slug, 'guard_name' => $guard],
                ['guard_name' => $guard]
            );
            $role->syncPermissions($permissions);

            $roleInstances[$roleName] = $role;
        }

        // Role inheritance (if defined in config)
        foreach ($rolesConfig as $roleName => $roleData) {
            if (isset($roleData['inherits'])) {
                $role = $roleInstances[$roleName];
                foreach ($roleData['inherits'] as $inheritedRoleName) {
                    $inheritedRole = $roleInstances[$inheritedRoleName];
                    $role->givePermissionTo($inheritedRole->permissions);
                }
            }
        }
    }
}