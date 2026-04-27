<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'product_id', 
        'category_id', 
        'action', 
        'action_time', 
        'device_type', 
        'ip_address', 
        'additional_data'
    ];

    /**
     * Get the user that owns the UserActivity.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that is associated with the UserActivity.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);  // A Category has many UserActivities
    }
}
