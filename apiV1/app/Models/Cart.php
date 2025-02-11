<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'customer_id', 'total_price', 'total_quantity'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
