<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBrand extends Model
{
    protected $fillable = ['name', 'logo'];

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
