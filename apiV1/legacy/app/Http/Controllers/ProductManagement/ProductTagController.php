<?php

namespace App\Http\Controllers\ProductManagement;

use App\Http\Controllers\ProductManagement\BaseController;
use App\Repositories\ProductManagement\TagRepository;
use App\Services\ProductManagementValidationService;

class ProductTagController extends BaseController
{
    public function __construct(TagRepository $repository)
    {
        parent::__construct($repository,app(ProductManagementValidationService::class));
    }
}