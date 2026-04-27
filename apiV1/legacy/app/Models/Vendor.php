<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    protected $fillable = ['user_id', 'contact_email', 'contact_phone', 'address', 'business_description', 'store_manager_id', 'logo', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function storeManager(): BelongsTo
    {
        return $this->belongsTo(StoreManager::class, 'store_manager_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VendorReview::class);
    }
}