<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategorie extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id'];

    public function parentCategory()
    {
        return $this->belongsTo(ProductCategorie::class, 'parent_id');
    }

    public function subCategories()
    {
        return $this->hasMany(SubCategorie::class, 'category_id');
    }
}
