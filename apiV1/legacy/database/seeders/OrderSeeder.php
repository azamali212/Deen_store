<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Example of creating 10 orders
        for ($i = 0; $i < 10; $i++) {
            // Calculate grand total manually
            $totalAmount = $faker->randomFloat(2, 100, 1000);
            $discount = $faker->randomFloat(2, 0, 100);
            $taxAmount = $faker->randomFloat(2, 5, 50);
            $shippingAmount = $faker->randomFloat(2, 5, 20);
            $grandTotal = $totalAmount - $discount + $taxAmount + $shippingAmount;

            DB::table('orders')->insert([
                'order_manager_id' => $faker->unique()->randomNumber(),
                'store_manager_id' => $faker->unique()->randomNumber(),
                'customer_id' => $faker->randomNumber(),
                'product_id' => $faker->randomNumber(),
                'vendor_id' => $faker->randomNumber(),
                'user_id' => \Illuminate\Support\Str::ulid(), // Assuming you have a UUID field for user_id
                'shipping_zone_id' => $faker->randomElement([null, $faker->randomNumber()]),
                'order_number' => $faker->unique()->regexify('[A-Z]{5}[0-9]{5}'),
                'discount' => $discount,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'grand_total' => $grandTotal,
                'payment_status' => $faker->randomElement(['pending', 'paid', 'failed', 'refunded']),
                'order_status' => $faker->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']),
                'tracking_number' => $faker->optional()->regexify('[A-Z0-9]{10}'),
                'shipping_address' => $faker->address,
                'billing_address' => $faker->address,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}