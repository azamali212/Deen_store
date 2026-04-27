<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Product extends Model
{
    use HasFactory;

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
        'brand_id',
        'supplier_id', 
        'is_supplier_product'
    ];

    //SearchAble Array 
    protected static function booted()
    {
        static::creating(function ($product) {
            \Log::info('Creating Event Triggered:', $product->toArray()); // Debugging
        });
    }

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
        return $this->belongsTo(ProductCategory::class, 'category_id');
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
        return $this->belongsToMany(ProductTag::class, 'product_tag_relations', 'product_id', 'tag_id');
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
    public function subcategory()
    {
        return $this->belongsTo(SubCategorie::class, 'subcategory_id');
    }
    public function purchases()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    /**
     * Get the product bundles associated with the product.
     */
    public function productBundles()
    {
        return $this->hasMany(Product_Bundle::class);
    }
    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Scope to filter only customer products
    public function scopeCustomerProducts($query)
    {
        return $query->where('is_supplier_product', false);
    }

    // Scope to filter only supplier products
    public function scopeSupplierProducts($query)
    {
        return $query->where('is_supplier_product', true);
    }
}
