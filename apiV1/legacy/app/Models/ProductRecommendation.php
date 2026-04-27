<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductRecommendation extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'recommended_product_ids'];

    // Cast the recommended_product_ids as an array since it's stored as JSON in the database
    protected $casts = [
        'recommended_product_ids' => 'array',
    ];
}
