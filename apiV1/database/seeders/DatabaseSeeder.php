<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Factories\EmailStatusFactory;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        \App\Models\Email::factory(10)->create();
        EmailStatusFactory::new()->count(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
        ]);
        $this->call(RoleAndPermissionSeeder::class);
        $this->call(ProductBrandSeeder::class);
        $this->call(ProductManagerSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(VendorSeeder::class);
        $this->call(ProductCategorySeeder::class);
        $this->call(CartSeeder::class);
        $this->call(OrderSeeder::class);
    }
}
