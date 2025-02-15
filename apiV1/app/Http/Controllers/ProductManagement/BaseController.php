<?php

namespace App\Http\Controllers\ProductManagement;

use App\Http\Controllers\Controller;
use App\Repositories\ProductManagement\BaseRepo\BaseRepositoryInterface;
use App\Services\ProductManagementValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

abstract class BaseController extends Controller
{
    protected BaseRepositoryInterface $repository;
    protected ProductManagementValidationService $validationService;

    public function __construct(
        BaseRepositoryInterface $repository,
        ProductManagementValidationService $validationService
    ) {
        $this->repository = $repository;
        $this->validationService = $validationService;
    }
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('perPage', 10);
        $relations = $request->get('relations', []);
        return response()->json($this->repository->getAll($perPage, $relations));
    }
    public function show(int $id, Request $request): JsonResponse
    {
        $relations = $request->get('relations', []);
        return response()->json($this->repository->findById($id, $relations));
    }
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate request data using the appropriate validation method
            $validatedData = $this->validateRequest($request);
            return response()->json($this->repository->create($validatedData), 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Validate request data using the appropriate validation method
            $validatedData = $this->validateRequest($request);
            return response()->json($this->repository->update($id, $validatedData));
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }
    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);
        return response()->json(['message' => 'Deleted successfully']);
    }
    protected function validateRequest(Request $request): array
    {
        $routeName = request()->route()->getName(); // Get current route name

        return match ($routeName) {
            'products.store', 'products.update' => $this->validationService->validateProduct($request->all()),
            'product-brands.store', 'product-brands.update' => $this->validationService->validateProductBrand($request->all()),
            'product-categories.store', 'product-categories.update' => $this->validationService->validateProductCategory($request->all()),
            'product-images.store', 'product-images.update' => $this->validationService->validateProductImage($request->all()),
            'product-tags.store', 'product-tags.update' => $this->validationService->validateProductTag($request->all()),
            'product-variants.store', 'product-variants.update' => $this->validationService->validateProductVariant($request->all()),
            'sub-categories.store', 'sub-categories.update' => $this->validationService->validateSubCategory($request->all()),
            default => $request->all(), // If no specific validation is needed
        };
    }
}
