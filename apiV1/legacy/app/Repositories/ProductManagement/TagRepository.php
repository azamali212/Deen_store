<?php

namespace App\Repositories\ProductManagement;

use App\Models\ProductTag;
use App\Repositories\ProductManagement\BaseRepo\BaseRepository;

class TagRepository extends BaseRepository
{
    public function __construct(ProductTag $model)
    {
        parent::__construct($model);
    }
}