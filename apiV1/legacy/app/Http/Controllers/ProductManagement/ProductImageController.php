<?php

namespace App\Http\Controllers\ProductManagement;

use App\Http\Controllers\ProductManagement\BaseController;
use App\Repositories\ProductManagement\ProductImageRepository;
use App\Services\ProductManagementValidationService;

class ProductImageController extends BaseController
{
    public function __construct(ProductImageRepository $repository)
    {
        parent::__construct($repository,app(ProductManagementValidationService::class));
    }
}