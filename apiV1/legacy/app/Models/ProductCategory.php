<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'product_manager_id',
        'store_manager_id',
        'parent_id',
        'description',
        'image',
        'is_active',
        'position',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Automatically generate slug when creating/updating category.
     */
    public static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    /**
     * Get the parent category.
     */
    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get subcategories for the category.
     */
    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategorie::class, 'category_id');
    }

    /**
     * Scope to filter only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get categories sorted by position.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('position', 'asc');
    }

    /**
     * Fetch all parent categories with their subcategories (Optimized Query).
     */
    public static function getAllWithSubCategories()
    {
        return self::with(['subCategories' => function ($query) {
            $query->select('id', 'category_id', 'name', 'slug');
        }])->whereNull('parent_id')->get();
    }
}