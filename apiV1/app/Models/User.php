<?php

namespace App\Models;

use App\Notifications\VerifyEmail;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;

/**
 * @method string createToken(string $name, array $abilities = ['*'])
 * @method \Illuminate\Support\Collection getRoleNames()
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes, Billable;

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'email_verification_token',
        'last_login_at',
        'last_login_ip',
        'last_known_location',
        'latitude',
        'longitude',
        'last_location_updated_at',
        'default_payment_method',
        'status',
        'deactivated_at',
        'deactivated_by',
        'deactivation_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $guard_name = 'api';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_location_updated_at' => 'datetime',
        'last_login_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'status' => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid();
            }
        });
    }

    public function activeSessions()
    {
        return $this->hasMany(ActiveSession::class);
    }

    public function deactivatedByUser()
    {
        return $this->belongsTo(User::class, 'deactivated_by', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDeactivated(): bool
    {
        return $this->status === 'inactive' && $this->deactivated_at !== null;
    }

    public function viewedCategories()
    {
        return $this->belongsToMany(ProductCategory::class, 'user_product_views', 'user_id', 'category_id');
    }

    public function viewedProducts()
    {
        return $this->belongsToMany(Product::class, 'user_product_views', 'user_id', 'product_id');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail());
    }

    protected function getVerificationUrl(): string
    {
        return url(route('verification.verify', ['token' => $this->email_verification_token], false));
    }

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

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (is_string($permission)) {
            return $this->checkPermissionByName($permission, $guardName);
        }

        return $this->hasDirectPermission($permission)
            || $this->hasPermissionViaRole($permission)
            || $this->hasTemporaryPermission($permission->name);
    }

    protected function checkPermissionByName(string $permissionName, $guardName = null): bool
    {
        if ($this->hasDirectPermission($permissionName)) {
            return true;
        }

        $permission = Permission::findByName($permissionName, $guardName ?: $this->getDefaultGuardName());

        if ($this->hasPermissionViaRole($permission)) {
            return true;
        }

        return $this->hasTemporaryPermission($permissionName);
    }

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

    protected function checkPermission($permission): bool
    {
        if (is_string($permission)) {
            return $this->checkPermissionByName($permission);
        }

        return $this->hasPermissionTo($permission);
    }

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

    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        $directPermissions = $this->getDirectPermissions();
        $rolePermissions = $this->getPermissionsViaRoles();

        $temporaryPermissions = $this->activeTemporaryPermissions()->get()->map(function ($tempPerm) {
            return $tempPerm->permission;
        });

        return $directPermissions
            ->merge($rolePermissions)
            ->merge($temporaryPermissions)
            ->unique('id');
    }

    public function getPermissionNames(): \Illuminate\Support\Collection
    {
        return collect($this->getAllPermissionNames());
    }

    public function can($ability, $arguments = []): bool
    {
        if (parent::can($ability, $arguments)) {
            return true;
        }

        return $this->hasTemporaryPermission($ability);
    }

    // New email module relations — assuming users.id is string ULID
    public function sentEmails()
    {
        return $this->hasMany(Email::class, 'sender_id', 'id');
    }

    public function emailRecipients()
    {
        return $this->hasMany(EmailRecipient::class, 'user_id', 'id');
    }

    public function emailMailboxes()
    {
        return $this->hasMany(EmailMailbox::class, 'user_id', 'id');
    }

    public function createdEmailThreads()
    {
        return $this->hasMany(EmailThread::class, 'created_by', 'id');
    }

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
                    'days_remaining' => $tempPerm->days_remaining,
                ];
            })->toArray(),
        ];
    }
}
