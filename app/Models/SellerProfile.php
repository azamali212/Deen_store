<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\BusinessType;
use App\Domain\Seller\Enums\SellerKycStatus;
use App\Domain\Seller\Enums\SellerProfileStatus;
use Carbon\CarbonInterface;
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
        'pending_store_name',
        'store_name_requested_at',
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
        'cnic_expires_at',
        'licence_expires_at',
        'kyc_status',
        'kyc_notified_at',
        'status',
        'suspension_reason',
        'suspended_by',
        'suspended_at',
        'closed_at',
        'closure_reason',
        'reopen_requested_at',
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
            'closed_at' => 'immutable_datetime',
            'store_name_requested_at' => 'immutable_datetime',
            'reopen_requested_at' => 'immutable_datetime',
            'cnic_expires_at' => 'immutable_date',
            'licence_expires_at' => 'immutable_date',
            'kyc_status' => SellerKycStatus::class,
            'kyc_notified_at' => 'immutable_datetime',
        ];
    }

    public function isSuspended(): bool
    {
        return $this->status === SellerProfileStatus::SUSPENDED;
    }

    public function isClosed(): bool
    {
        return $this->status === SellerProfileStatus::CLOSED;
    }

    /**
     * C33 — the single question every write guard asks. Adding a new
     * non-open state means changing SellerProfileStatus::isOpen() and
     * nothing else; no guard can be forgotten.
     */
    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function hasNameChangePending(): bool
    {
        return $this->pending_store_name !== null;
    }

    public function hasReopenRequestPending(): bool
    {
        return $this->isClosed() && $this->reopen_requested_at !== null;
    }

    // What the future Payouts domain will check before sending money.
    public function isPayoutBankVerified(): bool
    {
        return $this->bank_account_last4 !== null
            && $this->bank_verification_status?->isPayoutReady() === true;
    }

    /**
     * C27 — a store with no recorded expiry is VALID, not expired. Most
     * stores have no date at all because AI verification is off by default;
     * treating NULL as expired would freeze every existing seller's payouts
     * the day it is switched on.
     */
    public function kycStatus(): SellerKycStatus
    {
        return $this->kyc_status ?? SellerKycStatus::VALID;
    }

    /**
     * P8-3 — payout needs BOTH facts: the bank is proven theirs AND their
     * identity documents are still valid. Two questions, two columns.
     */
    public function isPayoutReady(): bool
    {
        return $this->isPayoutBankVerified() && ! $this->kycStatus()->blocksPayout();
    }

    /**
     * The soonest of the tracked expiry dates, or null when nothing is
     * known. This is what the daily job and the API both report on.
     */
    public function earliestKycExpiry(): ?CarbonInterface
    {
        $dates = array_filter([$this->cnic_expires_at, $this->licence_expires_at]);

        return $dates === [] ? null : min($dates);
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

    public function documentRenewals(): HasMany
    {
        return $this->hasMany(SellerDocumentRenewal::class);
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
