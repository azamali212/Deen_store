<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Auth\Enums\AuthPanel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ActiveSession extends Model
{
    protected $fillable = [
        'user_id',
        'token_id',
        'panel',
        'ip_address',
        'user_agent',
        'device_name',
        'browser',
        'operating_system',
        'last_activity_at',
        'terminated_at',
    ];

    protected function casts(): array
    {
        return [
            'panel' => AuthPanel::class,
            'last_activity_at' => 'datetime',
            'terminated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->terminated_at === null;
    }
}