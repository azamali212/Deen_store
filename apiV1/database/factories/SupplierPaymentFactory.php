<?php

namespace Database\Factories;

use App\Models\SupplierPayment;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierPaymentFactory extends Factory
{
    protected $model = SupplierPayment::class;

    public function definition()
    {
        return [
            'supplier_id' => Supplier::inRandomOrder()->first()->id,
            'amount' => $this->faker->randomFloat(2, 100, 1000),
            'payment_method' => $this->faker->word(),
            'payment_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['completed', 'pending', 'failed']),
        ];
    }
}
