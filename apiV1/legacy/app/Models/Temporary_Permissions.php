<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class Temporary_Permissions extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permission_id',
        'assigned_at',
        'expires_at',
        'is_active',
        'reason',
        'assigned_by',
        'revoked_at',
        'revoke_reason'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Since we're using ULID, we need to specify the key type
    protected $keyType = 'string';
    public $incrementing = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive()
    {
        return $this->is_active &&
            !$this->isExpired() &&
            is_null($this->revoked_at);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('expires_at', '>', now())
            ->whereNull('revoked_at');
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('expires_at', '<=', now())
                ->orWhere('is_active', false)
                ->orWhereNotNull('revoked_at');
        });
    }

    public function getDaysRemainingAttribute()
    {
        if (!$this->expires_at || $this->expires_at->isPast()) {
            return 0;
        }

        return now()->diffInDays($this->expires_at, false);
    }
}
