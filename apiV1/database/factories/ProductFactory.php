<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductManager;
use App\Models\StoreManager;
use App\Models\Vendor;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        $name = $this->faker->unique()->words(3, true);
        $slug = Str::slug($name);
        
        return [
            'name' => $name,
            'slug' => $slug,
            'product_manager_id' => ProductManager::inRandomOrder()->first()?->id ?? 1,
            'store_manager_id' => StoreManager::inRandomOrder()->first()?->id ?? 1,
            'vendor_id' => Vendor::inRandomOrder()->first()?->id ?? 1,
            'description' => $this->faker->sentence(10),
            'sku' => strtoupper(Str::random(10)),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'discount_price' => $this->faker->randomFloat(2, 5, 400),
            'stock_quantity' => $this->faker->numberBetween(1, 100),
            'weight' => $this->faker->randomFloat(2, 0.1, 10),
            'dimensions' => $this->faker->randomElement(['10x10x10', '20x15x5', '5x5x5']),
            'is_active' => $this->faker->boolean(90),
            'is_featured' => $this->faker->boolean(30),
            'category_id' => ProductCategory::inRandomOrder()->first()?->id ?? 1,
            'brand_id' => ProductBrand::inRandomOrder()->first()?->id ?? 1
        ];
    }
}