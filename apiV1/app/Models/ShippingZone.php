<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'country',
        'region',
        'shipping_cost',
    ];
    public function shippingRules()
    {
        return $this->hasMany(ShippingRule::class, 'shipping_zone_id');
    }
}
