<?php

namespace Database\Seeders;

use App\Domain\Permissions\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

final class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = config('zimal.super_admin.email', 'admin@zimal.test');

        $superAdmin = User::query()->updateOrCreate(

            ['email' => $email],

            [

                'name' => config('zimal.super_admin.name', 'ZIMAL Super Admin'),

                'password' => Hash::make(config('zimal.super_admin.password', 'ChangeMe@12345')),

                'email_verified_at' => now(),

                //'status' => 'active',

            ]

        );

        $role = Role::query()

            ->where('name', SystemRole::SUPER_ADMIN->value)

            ->where('guard_name', 'api')

            ->firstOrFail();

        if (! $superAdmin->hasRole($role)) {

            $superAdmin->assignRole($role);
        }
    }
}
