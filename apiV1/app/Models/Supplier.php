<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'supplier_category_id',  // Ensure this is present to define the relationship
        'is_preferred',
        'blacklisted',
        'blacklist_reason',
        'contract_status',
        'performance_rating'
    ];

    /**
     * A supplier belongs to a supplier category.
     */
    public function categories()
    {
        return $this->belongsTo(SupplierCategory::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query->when(isset($filters['name']), function ($q) use ($filters) {
            $q->where('name', 'like', "%{$filters['name']}%");
        })->when(isset($filters['status']), function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        });
    }

    /**
     * A supplier belongs to a category.
     */


    /**
     * A supplier has many purchase orders.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * A supplier has multiple payments.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
