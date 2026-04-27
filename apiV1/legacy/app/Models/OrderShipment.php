<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderShipment extends Model
{
    protected $fillable = [
        'order_id',
        'shipping_method_id',
        'tracking_number',
        'carrier_name',
        'status',
        'shipped_at',
        'estimated_delivery',
        'delivered_at',
    ];

    // Relationship to Order and ShippingMethod
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }
    public function deliveryManager()
    {
        return $this->belongsTo(DeliveryManager::class, 'delivery_manager_id');
    }

    // Belongs to a delivery address
    public function deliveryAddress()
    {
        return $this->belongsTo(DeliveryAddress::class, 'delivery_address_id');
    }
}
