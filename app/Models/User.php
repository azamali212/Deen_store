<?php

namespace App\Models;

use App\Domain\Auth\Enums\UserAccountStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'password',
        'status',
        'last_login_at',
        'last_login_ip',
        'email_verified_at',
        'failed_login_attempts',
        'locked_at',
        'locked_until',
        'lock_reason',
        'last_failed_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected string $guard_name = 'api';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserAccountStatus::class,

            'locked_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_failed_login_at' => 'datetime',
            'failed_login_attempts' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::ulid();
            }
        });
    }

    public function loginOtps(): HasMany
    {
        return $this->hasMany(LoginOtp::class);
    }

    public function activeSessions(): HasMany
    {
        return $this->hasMany(ActiveSession::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    public function isActive(): bool
    {
        return $this->status === UserAccountStatus::ACTIVE;
    }
}