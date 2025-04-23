<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order_Tracking extends Model
{
    use HasFactory;
    protected $table = 'order_tracking';

    protected $fillable = [
        'order_id',
        'status',
        'location',
        'latitude',
        'longitude',
        'updated_by',
        'source',
        'notes',
        'tracked_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'tracked_at' => 'datetime',
    ];

    /**
     * The order that this tracking entry belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The user who updated this tracking entry.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by location.
     */
    public function scopeLocation($query, string $location)
    {
        return $query->where('location', 'LIKE', "%$location%");
    }

    /**
     * Scope for filtering entries within a date range.
     */
    public function scopeDateRange($query, string $start, string $end)
    {
        return $query->whereBetween('tracked_at', [$start, $end]);
    }
}
