<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    public function run()
    {
        // Create 10 categories
        ProductCategory::factory(10)->create();

        // Optionally, create categories with specific attributes
        ProductCategory::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'parent_id' => null,
        ]);
    }
}