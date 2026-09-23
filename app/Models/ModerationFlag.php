<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Moderation\Enums\ModerationSeverity;
use App\Domain\Moderation\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ModerationFlag extends Model
{
    use HasFactory;

    protected $table = 'profile_moderation_flags';

    protected $fillable = [
        'user_id',
        'status',
        'severity',
        'flagged_fields',
        'ai_summary',
        'snapshot',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ModerationStatus::class,
            'severity' => ModerationSeverity::class,
            'flagged_fields' => 'array',
            'snapshot' => 'array',
            'reviewed_at' => 'immutable_datetime',
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
}
