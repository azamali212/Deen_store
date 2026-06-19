<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\AuthStatus;
use App\Domain\Auth\Enums\LoginProvider;
use App\Domain\Auth\Enums\LoginRiskLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoginLog extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'status',
        'panel',
        'provider',
        'risk_level',
        'ip_address',
        'user_agent',
        'device_name',
        'failure_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => AuthStatus::class,
            'panel' => AuthPanel::class,
            'provider' => LoginProvider::class,
            'risk_level' => LoginRiskLevel::class,
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}