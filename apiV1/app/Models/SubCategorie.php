<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCategorie extends Model
{
    protected $fillable = ['category_id', 'name', 'slug', 'description'];

    public function category()
    {
        return $this->belongsTo(ProductCategorie::class, 'category_id');
    }
}
