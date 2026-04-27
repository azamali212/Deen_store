<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingRoleRequest extends Model
{
    use HasFactory, SoftDeletes, HasUlids; // Add HasUlids trait

    protected $fillable = [
        'id', // Add id to fillable
        'name',
        'slug',
        'permission_names',
        'description',
        'created_by',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'rejected_at',
        'notified_at',
        'notification_attempts',
        'notification_logs',
        'escalated',
        'escalated_at',
        'metadata'
    ];

    protected $casts = [
        'permission_names' => 'array',
        'notification_logs' => 'array',
        'metadata' => 'array',
        'escalated' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'notified_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    // Specify that id is not auto-incrementing
    public $incrementing = false;
    protected $keyType = 'string';
    
    // Add this method for ULID generation
    public function uniqueIds()
    {
        return ['id'];
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeNeedsNotification($query, $hours = 24)
    {
        return $query->pending()
            ->where(function ($q) use ($hours) {
                $q->whereNull('notified_at')
                  ->orWhere('notified_at', '<', now()->subHours($hours));
            })
            ->where('notification_attempts', '<', 3);
    }

    public function scopeOverdue($query, $hours = 48)
    {
        return $query->pending()
            ->where('created_at', '<', now()->subHours($hours));
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function markAsNotified()
    {
        $this->update([
            'notified_at' => now(),
            'notification_attempts' => $this->notification_attempts + 1,
            'notification_logs' => array_merge(
                $this->notification_logs ?? [],
                [['timestamp' => now(), 'attempt' => $this->notification_attempts + 1]]
            )
        ]);
    }

    public function escalate()
    {
        $this->update([
            'escalated' => true,
            'escalated_at' => now(),
            'metadata' => array_merge(
                $this->metadata ?? [],
                ['escalation_reason' => 'Overdue for approval']
            )
        ]);
    }

    public function getDurationAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}