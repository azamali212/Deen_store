<?php

namespace App\Http\Controllers\ProductManagement;

use App\Http\Controllers\ProductManagement\BaseController;
use App\Repositories\ProductManagement\VariantRepository;
use App\Services\ProductManagementValidationService;

class ProductVariantController extends BaseController
{
    public function __construct(VariantRepository $repository)
    {
        parent::__construct($repository,app(ProductManagementValidationService::class));
    }
}