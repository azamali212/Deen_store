<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $table = 'central_notification_templates';

    protected $fillable = [
        'type',
        'channel',
        'locale',
        'subject_template',
        'body_template',
        'active',
        'version',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}