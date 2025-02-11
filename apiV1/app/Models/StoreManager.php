<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreManager extends Model
{
    protected $fillable = [
        'username',
        'status',
        'phone_number',
        'profile_picture'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'store_manager_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'store_manager_id');
    }

    public function vendors()
    {
        return $this->hasMany(Vendor::class, 'store_manager_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'store_manager_id');
    }
}
