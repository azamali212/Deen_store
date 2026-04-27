<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Product::factory()->count(10)->create([
            'limit' => 5, // You can specify different limits for seeding
        ]); // Creates 20 sample products
    }
}
