<?php 
namespace App\Http\Controllers\CategoryManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CategoryRequest;
use App\Http\Resources\Category\CategoryResource;
use App\Repositories\CategoryManagement\ParentCategoryRepo\CategoryRepositoryInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    protected $categoryRepo;

    public function __construct(CategoryRepositoryInterface $categoryRepo)
    {
        $this->categoryRepo = $categoryRepo;
    }

    public function index()
    {
        $categories = $this->categoryRepo->getAllCategories();
        return CategoryResource::collection($categories);
    }

    public function show($id)
    {
        $category = $this->categoryRepo->getCategoryById($id);
        return new CategoryResource($category);
    }

    public function store(CategoryRequest $request)
    {
        $category = $this->categoryRepo->createCategory($request->validated());
        // Removed event dispatch
        return response()->json([
            'message' => 'Category created successfully.',
            'data' => new CategoryResource($category)
        ], Response::HTTP_CREATED);
    }

    public function update(CategoryRequest $request, $id)
    {
        $category = $this->categoryRepo->updateCategory($id, $request->validated());
        // Removed event dispatch
        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => new CategoryResource($category)
        ]);
    }

    public function destroy($id)
    {
        $this->categoryRepo->deleteCategory($id);
        // Removed event dispatch
        return response()->json(['message' => 'Category deleted successfully.'], Response::HTTP_NO_CONTENT);
    }

    public function activeCategories()
    {
        $categories = $this->categoryRepo->getActiveCategories();
        return CategoryResource::collection($categories);
    }

    public function parentCategories()
    {
        $categories = $this->categoryRepo->getParentCategories();
        return CategoryResource::collection($categories);
    }

    public function categoriesWithSubcategories()
    {
        $categories = $this->categoryRepo->getCategoriesWithSubcategories();
        return CategoryResource::collection($categories);
    }

    public function search(Request $request)
    {
        $categories = $this->categoryRepo->searchCategories($request->input('keyword'));
        return CategoryResource::collection($categories);
    }

    public function bulkCreate(Request $request)
    {
        $request->validate(['categories' => 'required|array']);
        $this->categoryRepo->bulkCreateCategories($request->input('categories'));
        return response()->json(['message' => 'Categories created successfully.'], Response::HTTP_CREATED);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate(['categories' => 'required|array']);
        $this->categoryRepo->bulkUpdateCategories($request->input('categories'));
        return response()->json(['message' => 'Categories updated successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        $this->categoryRepo->bulkDeleteCategories($request->input('ids'));
        return response()->json(['message' => 'Categories deleted successfully.']);
    }

    public function reorder(Request $request)
    {
        $request->validate(['categoryOrder' => 'required|array']);
        $this->categoryRepo->reorderCategories($request->input('categoryOrder'));
        return response()->json(['message' => 'Categories reordered successfully.']);
    }

    public function softDelete($id)
    {
        $this->categoryRepo->softDeleteCategory($id);
        return response()->json(['message' => 'Category soft deleted successfully.']);
    }

    public function restore($id)
    {
        $this->categoryRepo->restoreCategory($id);
        return response()->json(['message' => 'Category restored successfully.']);
    }

    public function count()
    {
        $count = $this->categoryRepo->getCategoryCount();
        return response()->json(['count' => $count]);
    }
}