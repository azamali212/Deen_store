<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run()
    {
        // Define category names
        $categories = [
            'Electronics',
            'Clothing',
            'Furniture',
            'Books',
            'Toys',
            'Sports',
            'Automobiles',
            'Health & Beauty',
            'Home & Kitchen',
            'Gaming',
        ];

        // Create categories while ensuring unique slugs
        foreach ($categories as $category) {
            $slug = Str::slug($category);

            // Ensure the slug is unique by checking and appending a number if necessary
            $uniqueSlug = $slug;
            $counter = 1;
            while (ProductCategory::where('slug', $uniqueSlug)->exists()) {
                $uniqueSlug = $slug . '-' . $counter;
                $counter++;
            }

            // Create category with the unique slug
            ProductCategory::create([
                'slug' => $uniqueSlug,
                'name' => $category,
                'parent_id' => null,
            ]);
        }

        // Optionally, create additional random categories using factories
        ProductCategory::factory(10)->create();
    }
}