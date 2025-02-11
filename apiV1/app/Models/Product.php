<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'product_manager_id',
        'store_manager_id',
        'vendor_id',
        'slug',
        'description',
        'sku',
        'price',
        'discount_price',
        'stock_quantity',
        'weight',
        'dimensions',
        'is_active',
        'is_featured',
        'category_id',
        'brand_id'
    ];


    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function storeManager()
    {
        return $this->belongsTo(StoreManager::class, 'store_manager_id');
    }
    public function productManager()
    {
        return $this->belongsTo(ProductManager::class, 'product_manager_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    public function category()
    {
        return $this->belongsTo(ProductCategorie::class, 'category_id');
    }
    public function brand()
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function tags()
    {
        return $this->belongsToMany(ProductTagRelation::class, 'product_tag_relations');
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }
}
