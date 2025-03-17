<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order_Tracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'location',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
