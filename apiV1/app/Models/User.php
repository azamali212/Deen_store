<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use App\Notifications\VerifyEmail;
use Carbon\Carbon;
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
    use Notifiable;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes, Billable;

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
        'status', // e.g., 'active', 'inactive', 'banned'
        'deactivated_at',
        'deactivated_by',
        'deactivation_reason'
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

    public function activeSessions()
    {
        return $this->hasMany(ActiveSession::class);
    }


    // Relationship to the user who deactivated this account
    public function deactivatedByUser()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }


    // Scope for active users
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    // Scope for inactive users
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    // Check if user is active
    protected $casts = [
        'status' => 'string',
        'deactivated_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDeactivated(): bool
    {
        return $this->status === 'inactive' && $this->deactivated_at !== null;
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

    // In User model
    public function temporaryPermissions()
    {
        return $this->hasMany(Temporary_Permissions::class);
    }

    public function getActiveTemporaryPermissionsAttribute()
    {
        return $this->temporaryPermissions()
            ->with('permission')
            ->active()
            ->get();
    }

    public function activeTemporaryPermissions()
    {
        return $this->temporaryPermissions()
            ->with('permission')
            ->where('is_active', true)
            ->where('expires_at', '>', Carbon::now())
            ->whereNull('revoked_at');
    }

    /**
     * OVERRIDE Spatie's permission check to include temporary permissions
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // If it's a string, handle all permission types
        if (is_string($permission)) {
            return $this->checkPermissionByName($permission, $guardName);
        }

        // If it's a Permission object, use standard flow but include temporary permissions
        return $this->hasDirectPermission($permission) || 
               $this->hasPermissionViaRole($permission) ||
               $this->hasTemporaryPermission($permission->name);
    }

    /**
     * Check permission by name (handles all permission types)
     */
    protected function checkPermissionByName(string $permissionName, $guardName = null): bool
    {
        // Check direct permissions (string version)
        if ($this->hasDirectPermission($permissionName)) {
            return true;
        }

        // Check role permissions - convert to Permission object first
        $permission = Permission::findByName($permissionName, $guardName ?: $this->getDefaultGuardName());
        if ($this->hasPermissionViaRole($permission)) {
            return true;
        }

        // Check temporary permissions
        return $this->hasTemporaryPermission($permissionName);
    }

    /**
     * OVERRIDE: Check if user has any of the given permissions
     */
    public function hasAnyPermission(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if ($this->checkPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * OVERRIDE: Check if user has all of the given permissions
     */
    public function hasAllPermissions(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if (!$this->checkPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Internal permission check method
     */
    protected function checkPermission($permission): bool
    {
        if (is_string($permission)) {
            return $this->checkPermissionByName($permission);
        }

        // Handle Permission object
        return $this->hasPermissionTo($permission);
    }

    /**
     * Check if user has temporary permission
     */
    public function hasTemporaryPermission($permissionName): bool
    {
        return $this->temporaryPermissions()
            ->whereHas('permission', function ($query) use ($permissionName) {
                $query->where('name', $permissionName);
            })
            ->where('is_active', true)
            ->where('expires_at', '>', Carbon::now())
            ->whereNull('revoked_at')
            ->exists();
    }

    /**
     * Get all permission names (including temporary)
     */
    public function getAllPermissionNames(): array
    {
        $directPermissions = $this->getDirectPermissions()->pluck('name');
        $rolePermissions = $this->getPermissionsViaRoles()->pluck('name');
        $temporaryPermissions = $this->activeTemporaryPermissions()->get()->pluck('permission.name');

        return $directPermissions
            ->merge($rolePermissions)
            ->merge($temporaryPermissions)
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get all permissions (including temporary) - for Spatie compatibility
     */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        $directPermissions = $this->getDirectPermissions();
        $rolePermissions = $this->getPermissionsViaRoles();
        
        // Convert temporary permissions to Permission models
        $temporaryPermissions = $this->activeTemporaryPermissions()->get()->map(function ($tempPerm) {
            return $tempPerm->permission;
        });

        return $directPermissions
            ->merge($rolePermissions)
            ->merge($temporaryPermissions)
            ->unique('id');
    }

    /**
     * OVERRIDE: Get direct permission names (for compatibility)
     */
    public function getPermissionNames(): \Illuminate\Support\Collection
    {
        return collect($this->getAllPermissionNames());
    }

    /**
     * Enhanced permission check that works with gates and policies
     */
    public function can($ability, $arguments = []): bool
    {
        // First try the default Spatie check
        if (parent::can($ability, $arguments)) {
            return true;
        }

        // If default check fails, check temporary permissions
        return $this->hasTemporaryPermission($ability);
    }

    /**
     * Get complete permission data for frontend
     */
    public function getCompletePermissionData(): array
    {
        return [
            'all_permissions' => $this->getAllPermissionNames(),
            'direct_permissions' => $this->getDirectPermissions()->pluck('name')->toArray(),
            'role_permissions' => $this->getPermissionsViaRoles()->pluck('name')->toArray(),
            'temporary_permissions' => $this->activeTemporaryPermissions()->get()->map(function ($tempPerm) {
                return [
                    'id' => $tempPerm->id,
                    'permission_name' => $tempPerm->permission->name,
                    'permission_id' => $tempPerm->permission->id,
                    'expires_at' => $tempPerm->expires_at->toISOString(),
                    'reason' => $tempPerm->reason,
                    'assigned_at' => $tempPerm->created_at->toISOString(),
                    'assigned_by' => $tempPerm->assigned_by,
                    'days_remaining' => $tempPerm->days_remaining
                ];
            })->toArray()
        ];
    }
}