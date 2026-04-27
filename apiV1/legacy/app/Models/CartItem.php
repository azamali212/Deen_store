<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id', 'product_id', 'variant_id', 'quantity', 'price', 'discount_price', 'total_price'
    ];

    public function toArray()
    {
        $data = parent::toArray();

        // Ensure quantity is being returned correctly
        if (isset($data['quantity']) && is_numeric($data['quantity'])) {
            $data['quantity'] = (int) $data['quantity'];
        }

        return $data;
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
