<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTag extends Model
{
    protected $fillable = ['name'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tag_relations');
    }
    public function userActivities()
    {
        return $this->hasMany(UserActivity::class);
    }
}
