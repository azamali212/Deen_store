<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Enums\ConsentType;
use Database\Factories\UserConsentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line in the consent ledger.
 *
 * Note what is missing: no update() of `version`, no delete. The service
 * only ever inserts a row or stamps one of the two closing timestamps.
 */
final class UserConsent extends Model
{
    /** @use HasFactory<UserConsentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'version',
        'granted_at',
        'withdrawn_at',
        'superseded_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'type' => ConsentType::class,
            'granted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Active means: given, not taken back, and not replaced by a newer
     * version of the same wording.
     */
    public function isActive(): bool
    {
        return $this->withdrawn_at === null
            && $this->superseded_at === null;
    }

    /**
     * @param  Builder<UserConsent>  $query
     * @return Builder<UserConsent>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('withdrawn_at')
            ->whereNull('superseded_at');
    }
}
