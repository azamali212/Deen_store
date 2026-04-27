<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order_Cancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'reason',
        'cancellation_status',
        'cancelled_at',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
