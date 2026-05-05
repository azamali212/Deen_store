<?php

declare(strict_types=1);

namespace Database\Seeders;


use App\Domain\Permissions\Data\PermissionMap;
use App\Domain\Permissions\Data\RolePermissionMap as DataRolePermissionMap;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionMap::permissions() as $permissionName) { //Foreach loop to create permissions based on the permission map, ensuring all defined permissions are seeded into the database
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => PermissionMap::GUARD,
            ]);
        }

        foreach (DataRolePermissionMap::roles() as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => PermissionMap::GUARD,
            ]);

            $role->syncPermissions($permissions); //Sync permissions to the role, ensuring that the role has the correct permissions assigned based on the role permission map
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}