<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product_Bundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_id',
        'bundle_product_id',
        'bundle_quantity',
    ];

    /**
     * Get the main product for the bundle.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the bundled product.
     */
    public function bundleProduct()
    {
        return $this->belongsTo(Product::class, 'bundle_product_id');
    }
}
