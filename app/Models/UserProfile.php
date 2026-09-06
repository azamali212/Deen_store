<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Enums\Gender;
use App\Domain\User\Enums\ProfileVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class UserProfile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'username',
        'date_of_birth',
        'gender',
        'bio',
        'avatar_path',
        'avatar_provider',
        'website_url',
        'occupation',
        'company_name',
        'country_code',
        'timezone',
        'locale',
        'profile_visibility',
        'profile_completion',
    ];

    protected function casts(): array
    {
        return [

            'date_of_birth' => 'date',
            'profile_completion' => 'integer',
            'deleted_at' => 'datetime',
            'gender' => Gender::class,
            'profile_visibility' => ProfileVisibility::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
        );
    }
}
