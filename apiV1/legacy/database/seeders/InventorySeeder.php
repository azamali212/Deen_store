<?php

namespace Database\Seeders;

use App\Models\InventoryLog;
use App\Models\InventoryStock;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Create 5 warehouses
        $warehouses = Warehouse::factory(5)->create();

        // Create inventory stock for 10 products
        InventoryLog::factory(10)->create();

        // Create 30 inventory logs
        InventoryStock::factory(30)->create();
    }
}
