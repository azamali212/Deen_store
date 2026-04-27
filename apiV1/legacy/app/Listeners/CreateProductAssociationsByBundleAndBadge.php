<?php

namespace App\Listeners;

use App\Events\ProductCreatedByBundleAndBadge;
use App\Models\Product_Batche;
use App\Models\Product_Bundle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateProductAssociationsByBundleAndBadge
{
    use InteractsWithQueue;

    public function handle(ProductCreatedByBundleAndBadge $event)
    {
        $product = $event->product;

        // Create a default product batch
        Product_Batche::create([
            'product_id' => $product->id,
            'batch_number' => 'BATCH-' . strtoupper(uniqid()),
            'expiry_date' => now()->addYear(), // Set expiry date to 1 year from now
            'quantity' => 100, // Default quantity
        ]);

        // Create a default product bundle (optional logic)
        Product_Bundle::create([
            'name'=> $product->name,
            'product_id' => $product->id,
            'bundle_product_id' => $product->id, // You can modify this logic to bundle with another product
            'bundle_quantity' => 2, // Default bundle quantity
        ]);
    }
}
