<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use App\Notifications\VerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verification_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public $incrementing = false; // Important for non-integer primary keys
    protected $keyType = 'string'; // ULIDs are strings

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid(); // Auto-generate ULID
            }
        });
    }

    public function viewedCategories()
    {
        return $this->belongsToMany(ProductCategory::class, 'user_product_views', 'user_id', 'category_id');
    }

    public function viewedProducts()
    {
        return $this->belongsToMany(Product::class, 'user_product_views', 'user_id', 'product_id');
    }


    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail()); // No need to pass the user object anymore
    }

    protected function getVerificationUrl()
    {
        return url(route('verification.verify', ['token' => $this->email_verification_token], false));  // Ensure URL is built with token
    }
}
