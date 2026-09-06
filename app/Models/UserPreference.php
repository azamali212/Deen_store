<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'language',
        'currency',
        'timezone',
        'theme',
        'email_notifications',
        'sms_notifications',
        'push_notifications',
        'marketing_notifications',
    ];

    protected function casts(): array
    {
        return [

            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'marketing_notifications' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
        );
    }
}
