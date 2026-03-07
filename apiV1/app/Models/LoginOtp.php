<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LoginOtp extends Model
{
    protected $table = 'login_otps';

    protected $fillable = [
        'user_id',
        'identifier',
        'purpose',
        'code_hash',
        'expires_at',
        'consumed_at',
        'attempts',
        'max_attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}