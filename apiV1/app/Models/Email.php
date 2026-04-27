<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'thread_id',
        'parent_email_id',
        'sender_id',
        'subject',
        'body',
        'excerpt',
        'priority',
        'type',
        'status',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(EmailThread::class, 'thread_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_email_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_email_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailRecipient::class, 'email_id');
    }

    public function mailboxEntries(): HasMany
    {
        return $this->hasMany(EmailMailbox::class, 'email_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class, 'email_id');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }
}