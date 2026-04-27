<?php

namespace App\Repositories\ProductManagement;

use App\Models\ProductVariant;
use App\Repositories\ProductManagement\BaseRepo\BaseRepository;

class VariantRepository extends BaseRepository
{
    public function __construct(ProductVariant $model)
    {
        parent::__construct($model);
    }
}