<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $table = 'login_logs';
    protected $fillable = [
        'session_id',
        'user_id',
        'email',
        'login_portal',
        'guard',
        'ip',
        'country',
        'city',
        'timezone',
        'browser',
        'os',
        'device',
        'success',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];
}
