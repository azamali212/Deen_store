<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seller\Enums\AiRiskLevel;
use App\Domain\Seller\Enums\BusinessType;
use App\Domain\Seller\Enums\SellerApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class SellerApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_name',
        'business_name',
        'business_type',
        'status',
        'rejection_reason',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'document_processing_consent_at',
        'ai_risk_level',
        'ai_report',
        'ai_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'business_type' => BusinessType::class,
            'status' => SellerApplicationStatus::class,
            'submitted_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'document_processing_consent_at' => 'immutable_datetime',
            'ai_risk_level' => AiRiskLevel::class,
            // Cross-check results only (pass/warn/fail + messages) — it never
            // holds raw CNIC or account numbers, so plain JSON is fine here.
            'ai_report' => 'array',
            'ai_checked_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SellerApplicationDocument::class);
    }

    public function sellerProfile(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
    }
}
