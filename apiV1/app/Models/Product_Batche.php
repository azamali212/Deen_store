<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product_Batche extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'expiry_date',
        'quantity',
    ];

    /**
     * Get the product that belongs to the batch.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
