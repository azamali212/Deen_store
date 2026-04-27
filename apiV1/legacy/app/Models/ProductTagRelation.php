<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTagRelation extends Model
{
    protected $fillable = ['product_id', 'tag_id'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function tag()
    {
        return $this->belongsTo(ProductTag::class, 'tag_id');
    }
}
