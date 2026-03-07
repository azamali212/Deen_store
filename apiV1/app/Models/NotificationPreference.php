<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $table = 'central_notification_preferences';

    protected $fillable = [
        'user_id',
        'notification_type',
        'channel',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}