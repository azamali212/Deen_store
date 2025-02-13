<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'name', 'subscribed_at', 'is_active', 'unsubscribed_at'];

    public $timestamps = true; // Ensures created_at & updated_at are managed

    /**
     * Scope to get only active subscribers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function customer()
{
    return $this->belongsTo(Customer::class, 'email', 'email'); 
}
}
