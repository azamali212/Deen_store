<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductManager;
use Illuminate\Support\Str;

class ProductManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Creating a default ProductManager entry
        ProductManager::create([
            'user_id' => Str::ulid()->toString(),  // Example of generating ULID for user_id
            'username' => 'default_manager',
            'phone_number' => '1234567890', 
            'status' => 'active', // Default status
        ]);

        // You can add more product managers here
        ProductManager::create([
            'user_id' => Str::ulid()->toString(),
            'username' => 'manager_two',
            'phone_number' => '0987654321',
            'status' => 'inactive',
        ]);
    }
}