<?php

namespace App\Http\Controllers\ProductManagement;

use App\Http\Controllers\ProductManagement\BaseController;
use App\Repositories\ProductManagement\CategoryRepository;
use App\Services\ProductManagementValidationService;

class CategoryController extends BaseController
{
    public function __construct(CategoryRepository $repository)
    {
        parent::__construct($repository,app(ProductManagementValidationService::class));
    }
}