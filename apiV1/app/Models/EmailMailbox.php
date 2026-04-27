<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmailMailbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_id',
        'user_id',
        'owner_type',
        'folder',
        'is_read',
        'is_starred',
        'is_draft',
        'read_at',
        'starred_at',
        'trashed_at',
        'restored_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'is_draft' => 'boolean',
        'read_at' => 'datetime',
        'starred_at' => 'datetime',
        'trashed_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class, 'email_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeInbox(Builder $query): Builder
    {
        return $query->where('folder', 'inbox');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('folder', 'sent');
    }

    public function scopeDrafts(Builder $query): Builder
    {
        return $query->where('folder', 'draft');
    }

    public function scopeTrash(Builder $query): Builder
    {
        return $query->where('folder', 'trash');
    }
}