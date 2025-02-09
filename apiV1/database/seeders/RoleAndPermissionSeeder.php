<?php

namespace Database\Seeders;

use App\Data\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
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
                'guard_name' => 'api',
            ]);
        }

        // Load roles and permissions from config/roles.php
        $rolesConfig = config('roles');

        // Create roles and assign permissions
        //Load all role and permission from the config file and after irtarate the loop on the role and permissions after first we crerate empty role array and the iterate the loop on the role and permission in this role namd with assciated permission and then we create the role and then we sync the permission with the role and then we store the role instance in the role instance array and 
        //if Permission have all then show all permsion of the role otherwise just those permissions show which assign
        $roleInstances = [];
        foreach ($rolesConfig as $roleName => $roleData) {
            $permissions = $roleData['permissions'] === 'all'
                ? Permission::all()
                : Permission::whereIn('name', $roleData['permissions'])->get();

            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
            $role->syncPermissions($permissions);

            // Store the role instance for inheritance (if applicable)
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