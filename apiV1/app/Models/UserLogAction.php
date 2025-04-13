<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLogAction extends Model
{
    protected $table = 'user_log_actions';

    protected $keyType = 'string'; // since you're using ULID
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'action',
        'event_type',
        'details',
        'status',
        'ip_address',
        'user_agent',
        'device_type',
        'device_model',
        'platform',
        'browser',
        'location',
        'latitude',
        'longitude',
        'route_name',
        'url',
        'performed_by',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'details'   => 'array',
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    /**
     * The user who the action was done to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The user who performed the action.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * The polymorphic related model (e.g., Nurse, Shift, etc.).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Filter by action type.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Logs performed by a specific user.
     */
    public function scopePerformedBy($query, $userId)
    {
        return $query->where('performed_by', $userId);
    }

    /**
     * Scope: Logs related to a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if action was successful.
     */
    public function isSuccess(): bool
    {
        return strtolower($this->status) === 'success';
    }

    /**
     * Geolocation helper.
     */
    public function getGeoLocationAttribute(): ?string
    {
        if ($this->latitude && $this->longitude) {
            return "{$this->latitude}, {$this->longitude}";
        }
        return null;
    }

    /**
     * Shorten user agent for display.
     */
    public function getShortUserAgentAttribute(): string
    {
        return str($this->user_agent)->limit(50);
    }
}