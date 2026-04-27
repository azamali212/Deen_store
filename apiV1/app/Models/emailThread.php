<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class EmailThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'subject',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class, 'thread_id');
    }
}
