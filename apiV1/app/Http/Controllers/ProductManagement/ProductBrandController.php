<?php

namespace App\Http\Controllers\ProductManagement;

use App\Http\Controllers\ProductManagement\BaseController;
use App\Repositories\ProductManagement\BrandRepository;
use App\Services\ProductManagementValidationService;

class ProductBrandController extends BaseController
{
    public function __construct(BrandRepository $repository)
    {
        parent::__construct($repository,app(ProductManagementValidationService::class));
    }
}