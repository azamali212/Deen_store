<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\SupplierCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company(),
            'contact_person' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->email(),
            'address' => $this->faker->address(),
            'supplier_category_id' => SupplierCategory::inRandomOrder()->first()->id ?? null,
            'is_preferred' => $this->faker->boolean(),
            'blacklisted' => $this->faker->boolean(),
            'blacklist_reason' => $this->faker->sentence(),
            'contract_status' => $this->faker->randomElement(['active', 'terminated', 'pending']),
            'performance_rating' => $this->faker->randomFloat(2, 0, 5),
        ];
    }
}
