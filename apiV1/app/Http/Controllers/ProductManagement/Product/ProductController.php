<?php

namespace App\Http\Controllers\ProductManagement\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductRequest;
use App\Repositories\ProductManagement\ProductRepo\ProductRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    protected $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $products = $this->productRepository->getAllProducts($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $products
        ], 200);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = $this->productRepository->createProduct($request->validated());
        return response()->json(['success' => true, 'data' => $product, 'message' => 'Product created successfully'], 201);
    }

    public function show($id): JsonResponse
{
    $product = $this->productRepository->getProduct($id);
    return response()->json(['success' => true, 'data' => $product], 200);
}

    public function update(ProductRequest $request, $id): JsonResponse
    {
        $product = $this->productRepository->updateProduct($request->validated(), $id);
        return response()->json(['success' => true, 'data' => $product, 'message' => 'Product updated successfully'], 200);
    }

    public function destroy($id): JsonResponse
    {
        $this->productRepository->deleteProduct($id);
        return response()->json(['success' => true, 'message' => 'Product deleted successfully'], 200);
    }

    public function filter(Request $request): JsonResponse
    {
        $filters = $request->all();
        $products = $this->productRepository->filterProducts($filters);
        return response()->json(['success' => true, 'data' => $products], 200);
    }

    public function sortAndPaginate(Request $request): JsonResponse
    {
        $filters = $request->except(['sort_by', 'sort_direction', 'per_page']);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $perPage = $request->get('per_page', 10);

        $products = $this->productRepository->getSortedAndPaginatedProducts($filters, $sortBy, $sortDirection, $perPage);
        return response()->json(['success' => true, 'data' => $products], 200);
    }

}
