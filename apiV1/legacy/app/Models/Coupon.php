<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $fillable = [
        'code', 'discount_type', 'discount_value', 'min_order_value', 'max_discount', 'usage_limit', 
        'used_count', 'expires_at', 'is_active', 'user_id', 'group_id'
    ];

    // Define relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define relationship with the Group model
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}