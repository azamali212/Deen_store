<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TrustedDevice extends Model
{
    protected $fillable = [
        'user_id',
        'fingerprint',
        'device_name',
        'ip_address',
        'user_agent',
        'trusted_until',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'trusted_until' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTrusted(): bool
    {
        return $this->trusted_until === null || $this->trusted_until->isFuture();
    }
}