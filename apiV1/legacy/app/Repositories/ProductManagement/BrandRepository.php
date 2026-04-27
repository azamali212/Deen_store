<?php

namespace App\Repositories\ProductManagement;

use App\Models\Brand;
use App\Models\ProductBrand;
use App\Repositories\ProductManagement\BaseRepo\BaseRepository;

class BrandRepository extends BaseRepository
{
    public function __construct(ProductBrand $model)
    {
        parent::__construct($model);
    }
}