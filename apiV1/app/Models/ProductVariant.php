<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'attribute', 'value', 'extra_price', 'stock_quantity'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
