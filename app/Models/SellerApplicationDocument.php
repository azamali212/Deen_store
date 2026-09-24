<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seller\Enums\DocumentAiStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SellerApplicationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_application_id',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'ai_status',
        'ai_findings',
        'ai_checked_at',
        'file_purged_at',
    ];

    // Private-disk path: kept out of any accidental toArray()/JSON dump.
    protected $hidden = [
        'file_path',
        'ai_findings',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => SellerDocumentType::class,
            'size_bytes' => 'integer',
            'ai_status' => DocumentAiStatus::class,
            // What the AI read off the document (CNIC number, names, dates,
            // account last4). Personal data -> ENCRYPTED at rest, like the
            // payout bank number.
            'ai_findings' => 'encrypted:array',
            'ai_checked_at' => 'immutable_datetime',
            'file_purged_at' => 'immutable_datetime',
        ];
    }

    /** P9-2 — the row outlives the file. */
    public function isPurged(): bool
    {
        return $this->file_purged_at !== null;
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(SellerApplication::class, 'seller_application_id');
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
}
