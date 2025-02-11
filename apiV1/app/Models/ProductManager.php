<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductManager extends Model
{
    //

    public function products()
    {
        return $this->hasMany(Product::class, 'product_manager_id');
    }
}
