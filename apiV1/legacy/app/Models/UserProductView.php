<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserProductView extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'category_id','product_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
