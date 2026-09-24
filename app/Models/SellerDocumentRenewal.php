<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seller\Enums\DocumentAiStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Enums\SellerRenewalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 8a — a fresh copy of an expiring document, uploaded by a LIVE
 * seller. Kept apart from seller_application_documents on purpose (C28).
 */
final class SellerDocumentRenewal extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_profile_id',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'ai_status',
        'ai_findings',
        'ai_checked_at',
        'extracted_expiry_date',
        'status',
        'reviewed_by',
        'reviewed_at',
        'file_purged_at',
        'rejection_reason',
    ];

    protected $hidden = [
        // Private-disk path — never leaves the server (D7).
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => SellerDocumentType::class,
            'status' => SellerRenewalStatus::class,
            'ai_status' => DocumentAiStatus::class,
            'size_bytes' => 'integer',
            // Names and the CNIC number the AI read: encrypted at rest,
            // exactly like seller_application_documents.
            'ai_findings' => 'encrypted:array',
            'ai_checked_at' => 'immutable_datetime',
            'file_purged_at' => 'immutable_datetime',
            'extracted_expiry_date' => 'immutable_date',
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function aiFields(): array
    {
        return is_array($this->ai_findings['fields'] ?? null) ? $this->ai_findings['fields'] : [];
    }

    /**
     * @return array<int, string>
     */
    public function aiConcerns(): array
    {
        return is_array($this->ai_findings['concerns'] ?? null) ? $this->ai_findings['concerns'] : [];
    }

    public function isPending(): bool
    {
        return $this->status === SellerRenewalStatus::PENDING;
    }

    /** P9-2 — the row outlives the file. */
    public function isPurged(): bool
    {
        return $this->file_purged_at !== null;
    }

    public function sellerProfile(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
