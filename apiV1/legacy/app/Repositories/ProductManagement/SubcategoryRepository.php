<?php

namespace App\Repositories\ProductManagement;

use App\Models\SubCategorie;
use App\Repositories\ProductManagement\BaseRepo\BaseRepository;

class SubcategoryRepository extends BaseRepository
{
    public function __construct(SubCategorie $model)
    {
        parent::__construct($model);
    }
}