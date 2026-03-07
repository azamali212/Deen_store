<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    protected $table = 'central_notifications';

    protected $fillable = [
        'recipient_id',
        'recipient_type',
        'type',
        'payload',
        'channels',
        'locale',
        'status',
        'actor_id',
        'idempotency_key',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'channels' => 'array',
        'sent_at' => 'datetime',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'notification_id');
    }
}