<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seller\Enums\SellerTeamMemberStatus;
use App\Domain\Seller\Enums\SellerTeamRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SellerTeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_profile_id',
        'user_id',
        'role',
        'status',
        'invited_by',
        'invited_at',
        'accepted_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => SellerTeamRole::class,
            'status' => SellerTeamMemberStatus::class,
            'invited_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status->grantsAccess();
    }

    public function isOwner(): bool
    {
        return $this->role === SellerTeamRole::OWNER;
    }

    public function sellerProfile(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
