<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $fillable = [
        'username',
        'address',
        'phone_number',
        'profile_picture'
    ];

    //Define Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
