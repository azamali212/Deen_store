<?php

namespace Database\Factories;

use App\Models\SupplierCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierCategoryFactory extends Factory
{
    protected $model = SupplierCategory::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
        ];
    }
}
