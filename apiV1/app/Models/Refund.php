<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'payment_id',
        'refund_amount',
        'refund_reason',
        'status',
        'processed_by',
        'processed_at',
    ];

    /**
     * Relationship: A refund belongs to an order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Relationship: A refund belongs to a customer (User).
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Relationship: A refund belongs to a payment.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /**
     * Relationship: A refund is processed by an admin (User).
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
