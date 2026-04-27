<?php

namespace App\Http\Controllers\ProductManagement;

use App\Http\Controllers\ProductManagement\BaseController;
use App\Repositories\ProductManagement\SubcategoryRepository;
use App\Services\ProductManagementValidationService;

class SubCategoryController extends BaseController
{
    public function __construct(SubcategoryRepository $repository)
    {
        parent::__construct($repository,validationService: app(ProductManagementValidationService::class));
    }
}