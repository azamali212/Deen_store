<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'username',
        'address',
        'phone_number',
        'profile_picture',
        'post_code',
        'city',
        'country',
        'status',
        'preferred_language',
        'newsletter_subscription'
    ];

    // Define Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
   public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for suspended customers only
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }
    public function storeManager()
    {
        return $this->belongsTo(StoreManager::class, 'store_manager_id');
    }
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
}
