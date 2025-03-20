<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
    ];

    /**
     * Get the inventory stocks associated with the warehouse.
     */
    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }
}
