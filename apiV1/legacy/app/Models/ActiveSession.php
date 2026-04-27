<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveSession extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'last_activity_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
