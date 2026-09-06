<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\User\Enums\AddressType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'is_default',
        'label',
        'recipient_name',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'type' => AddressType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
        );
    }
}
