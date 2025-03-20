<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'auto_restock_threshold',
    ];

    /**
     * Get the product that belongs to the inventory stock.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse where the stock is located.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
