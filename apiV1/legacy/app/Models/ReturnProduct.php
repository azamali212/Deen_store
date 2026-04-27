<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnProduct extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'order_id',
        'customer_id',
        'product_id',
        'reason',
        'status',
        'refund_id',
        'return_date',
        'processed_at',
    ];

    /**
     * Relationship: A return belongs to an order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Relationship: A return belongs to a customer (User).
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Relationship: A return belongs to a product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relationship: A return may have an associated refund.
     */
    public function refund()
    {
        return $this->belongsTo(Refund::class, 'refund_id');
    }
}
