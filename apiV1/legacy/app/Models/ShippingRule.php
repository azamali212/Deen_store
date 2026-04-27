<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRule extends Model
{
    protected $fillable = [
        'shipping_zone_id',
        'min_weight',
        'max_weight',
        'extra_cost',
        'min_order_value',
        'max_order_value',
    ];

    /**
     * Relationship: Each shipping rule belongs to one shipping zone.
     */
    public function shippingZone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
