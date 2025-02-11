<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'full_name',
        'phone_number',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default',
    ];

    // Relationship with Customer (User)
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function shipments()
    {
        return $this->hasMany(OrderShipment::class, 'delivery_address_id');
    }
}
