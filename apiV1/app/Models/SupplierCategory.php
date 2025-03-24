<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierCategory extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];


    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'supplier_category_id');
    }
}
