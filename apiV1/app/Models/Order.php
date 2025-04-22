<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_manager_id',
        'store_manager_id',
        'customer_id',
        'product_id',
        'vendor_id',
        'user_id',
        'shipping_zone_id',
        'order_number',
        'subtotal',
        'discount',
        'tax',
        'shipping_cost',
        'grand_total',
        'payment_status',
        'order_status',
        'tracking_number',
        'shipping_address',
        'billing_address',
        'order_date',
    ];

    const ESCALATION_PERIOD = 5; // Days after which an order is escalated

    // Check if the order is delayed (e.g., more than 5 days old)
    public function isDelayed(): bool
    {
        $now = Carbon::now();
        // Ensure delayed_at is a Carbon instance
        $delayedAt = Carbon::parse($this->delayed_at);

        // Assuming that a delayed order is one that is more than an hour old
        return $delayedAt->lessThan($now);
    }

    // Mark the order as delayed and store the delayed_at timestamp
    public function markAsDelayed(): void
    {
        $this->delayed_at = Carbon::now();
        $this->save();
    }

    // Escalate the order and mark the escalation timestamp
    public function escalateOrder(): void
    {
        $this->order_status = 'escalated';
        $this->escalated_at = Carbon::now();
        $this->escalation_status = 'escalated';
        $this->save();
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

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

    public function cancellations()
    {
        return $this->hasOne(Order_Cancellation::class);
    }

    public function tracking()
    {
        return $this->hasMany(Order_Tracking::class);
    }

    protected static function booted()
    {
        static::creating(function ($order) {
            $order->order_number = 'ORD-' . strtoupper(uniqid());
        });
    }
}
