<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryManager extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'status',
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shipments()
    {
        return $this->hasMany(OrderShipment::class, 'delivery_manager_id');
    }
}
