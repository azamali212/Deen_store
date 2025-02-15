<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor;
use App\Models\User;
use App\Models\StoreManager;
use Illuminate\Support\Str;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ensure that there is at least one StoreManager in the database
        $storeManager = StoreManager::inRandomOrder()->first();
        
        if (!$storeManager) {
            // Handle the case where no StoreManager exists
            $user = User::factory()->create([
                'id' => Str::ulid()->toString(), // Use ULID here
            ]);

            $storeManager = StoreManager::create([
                'user_id'=> $user->id, // Using the valid ULID
                'username' => 'Default Store Manager', 
                'status' => 'active',
                'profile_picture' => 'default_profile_picture.png',
                'phone_number' => '123456789',
            ]);
        }

        // Example of creating vendors with related data like user and store manager
        $vendors = [
            [
                'user_id' => User::inRandomOrder()->first()->id, // Assumes there's at least one user in the database
                'contact_email' => 'vendor1@example.com',
                'contact_phone' => '1234567890',
                'address' => '123 Vendor Street, City, Country',
                'business_description' => 'An amazing vendor selling top products.',
                'store_manager_id' => $storeManager->id, // Using the store manager's id
                'logo' => 'vendor1_logo.png',
                'status' => 'active',
            ],
            [
                'user_id' => User::inRandomOrder()->first()->id,
                'contact_email' => 'vendor2@example.com',
                'contact_phone' => '0987654321',
                'address' => '456 Vendor Avenue, City, Country',
                'business_description' => 'Best quality products available here.',
                'store_manager_id' => $storeManager->id, // Using the store manager's id
                'logo' => 'vendor2_logo.png',
                'status' => 'inactive',
            ],
            // Add more vendors as needed
        ];

        foreach ($vendors as $vendorData) {
            Vendor::create($vendorData);
        }
    }
}