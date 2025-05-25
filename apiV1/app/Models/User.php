<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use App\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     required={"id", "name", "email"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", example="john@example.com"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes,Billable;

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
        'last_login_at', 
        'default_payment_method',
    ];

    protected $guard_name = 'api';

    //protected $dates = ['deleted_at'];

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

    public function roles()
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }

public function permissions()
{
    return $this->morphToMany(Permission::class, 'model', 'model_has_permissions', 'model_id', 'permission_id');
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

    public function sentEmails()
    {
        return $this->hasMany(Email::class, 'sender_id');
    }

    /**
     * Get the emails that the user has received.
     */
    public function receivedEmails()
    {
        return $this->hasMany(Email::class, 'receiver_id');
    }

    /**
     * Get the email statuses for the emails the user has received or sent.
     */
    public function emailStatuses()
    {
        return $this->hasManyThrough(Email_Status::class, Email::class);
    }
}
