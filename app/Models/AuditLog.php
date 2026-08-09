<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Enums\AuditCategory;
use App\Domain\Audit\Enums\AuditSeverity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

final class AuditLog extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_type',
        'actor_id',
        'subject_type',
        'subject_id',
        'action',
        'category',
        'severity',
        'status',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'panel',
        'ip_address',
        'user_agent',
        'device_name',
        'request_id',
        'correlation_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'category' => AuditCategory::class,
            'severity' => AuditSeverity::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function uniqueIds(): array
    {
        return [
            'uuid',
        ];
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function delete(): ?bool
    {
        throw new LogicException(
            'Audit logs are immutable and cannot be deleted.',
        );
    }
}
