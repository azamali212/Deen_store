<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderManager extends Model
{
    use HasFactory;

    // Define the table if it's different from the default name
    protected $table = 'order_managers';

    // Fillable attributes
    protected $fillable = [
        'user_id',
        'username',
        'phone_number',
        'status',
    ];

    /**
     * Relationship to the User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}