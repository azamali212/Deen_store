<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductBrand;

class ProductBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Creating product brands with logos
        $brands = [
            ['name' => 'Apple', 'logo' => 'apple_logo.png'],
            ['name' => 'Samsung', 'logo' => 'samsung_logo.png'],
            ['name' => 'Nike', 'logo' => 'nike_logo.png'],
            ['name' => 'Adidas', 'logo' => 'adidas_logo.png'],
            ['name' => 'Sony', 'logo' => 'sony_logo.png'],
            // Add more brands as needed
        ];

        foreach ($brands as $brandData) {
            ProductBrand::create($brandData);
        }
    }
}