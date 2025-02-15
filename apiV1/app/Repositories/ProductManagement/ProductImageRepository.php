<?php

namespace App\Repositories\ProductManagement;

use App\Models\ProductImage;
use App\Repositories\ProductManagement\BaseRepo\BaseRepository;

class ProductImageRepository extends BaseRepository
{
    public function __construct(ProductImage $model)
    {
        parent::__construct($model);
    }
}