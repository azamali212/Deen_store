<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\BusinessType;
use App\Domain\Seller\Enums\SellerProfileStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SellerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'seller_application_id',
        'store_name',
        'business_name',
        'business_type',
        'logo_path',
        'description',
        'business_address',
        'bank_account_title',
        'bank_name',
        'bank_account_number',
        'bank_account_last4',
        'bank_verification_status',
        'bank_proof_path',
        'bank_proof_original_name',
        'bank_proof_uploaded_at',
        'bank_verified_by',
        'bank_verified_at',
        'bank_rejection_reason',
        'status',
        'suspension_reason',
        'suspended_by',
        'suspended_at',
    ];

    // Second safety net for D3: even if someone returns the model directly
    // instead of SellerProfileResource, the full number never leaves.
    protected $hidden = [
        'bank_account_number',
        // Private-disk path — never leaves the server.
        'bank_proof_path',
    ];

    protected function casts(): array
    {
        return [
            'business_type' => BusinessType::class,
            'status' => SellerProfileStatus::class,
            // Encrypted with APP_KEY on write, decrypted on read — the DB
            // column only ever holds ciphertext.
            'bank_account_number' => 'encrypted',
            'bank_verification_status' => BankVerificationStatus::class,
            'bank_proof_uploaded_at' => 'immutable_datetime',
            'bank_verified_at' => 'immutable_datetime',
            'suspended_at' => 'immutable_datetime',
        ];
    }

    public function isSuspended(): bool
    {
        return $this->status === SellerProfileStatus::SUSPENDED;
    }

    // What the future Payouts domain will check before sending money.
    public function isPayoutBankVerified(): bool
    {
        return $this->bank_account_last4 !== null
            && $this->bank_verification_status?->isPayoutReady() === true;
    }

    public function hasBankProofPending(): bool
    {
        return $this->bank_verification_status === BankVerificationStatus::PENDING_REVIEW
            && $this->bank_proof_path !== null;
    }

    public function maskedBankAccount(): ?string
    {
        return $this->bank_account_last4 !== null
            ? '****'.$this->bank_account_last4
            : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(SellerApplication::class, 'seller_application_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(SellerTeamMember::class);
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function bankVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bank_verified_by');
    }
}
