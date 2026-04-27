<?php

namespace App\Repositories\ProductManagement;


use App\Models\ProductCategorie;
use App\Repositories\ProductManagement\BaseRepo\BaseRepository;

class CategoryRepository extends BaseRepository
{
    public function __construct(ProductCategorie $model)
    {
        parent::__construct($model);
    }
}