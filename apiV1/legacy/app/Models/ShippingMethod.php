<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'description',
        'estimated_days',
        'base_cost',
        'cost_per_kg',
        'is_active',
    ];

    // Relationship with OrderShipments
    public function orderShipments()
    {
        return $this->hasMany(OrderShipment::class);
    }
}
