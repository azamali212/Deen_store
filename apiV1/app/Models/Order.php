<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_manager_id',
        'store_manager_id',
        'customer_id',
        'vendor_id',
        'order_number',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'grand_total',
        'user_id',
        'payment_status',
        'order_status',
        'tracking_number',
        'shipping_address',
        'billing_address'
    ];



    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function storeManager()
    {
        return $this->belongsTo(StoreManager::class, 'store_manager_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderManager()
    {
        return $this->belongsTo(OrderManager::class, 'order_manager_id');
    }
    public function shippingZone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
