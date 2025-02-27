<?php
namespace App\Repositories\CategoryManagement\ParentCategoryRepo;

use App\Events\CategoryUpdated;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoryRepository implements CategoryRepositoryInterface
{
    protected $cacheKey = 'categories';

    public function getAllCategories()
    {
        return Cache::remember($this->cacheKey, now()->addMinutes(30), function () {
            return ProductCategory::with('subCategories')->orderBy('position')->get();
        });
    }

    public function getCategoryById($id)
    {
        return ProductCategory::with('subCategories')->findOrFail($id);
    }

    public function createCategory(array $data)
    {
        $category = ProductCategory::create($data);
        $this->clearCache();
        // Removed broadcasting event
        return $category;
    }

    public function updateCategory($id, array $data)
    {
        $category = ProductCategory::findOrFail($id);
        $category->update($data);
        $this->clearCache();
        // Removed broadcasting event
        return $category;
    }

    public function deleteCategory($id)
    {
        $category = ProductCategory::findOrFail($id);
        $category->delete();
        $this->clearCache();
        // Removed broadcasting event
        return true;
    }

    public function getActiveCategories()
    {
        return ProductCategory::where('is_active', true)->orderBy('position')->get();
    }

    public function getParentCategories()
    {
        return ProductCategory::whereNull('parent_id')->get();
    }

    public function getCategoriesWithSubcategories()
    {
        return ProductCategory::with('subCategories')->get();
    }

    public function getCategoryBySlug(string $slug)
    {
        return ProductCategory::where('slug', $slug)->firstOrFail();
    }

    public function searchCategories(string $keyword)
    {
        return ProductCategory::where('name', 'LIKE', "%$keyword%")->get();
    }

    public function clearCache()
    {
        Cache::forget($this->cacheKey);
    }

    public function bulkCreateCategories(array $categories)
    {
        DB::table('product_categories')->insert($categories);
        $this->clearCache();
    }

    public function bulkUpdateCategories(array $categories)
    {
        foreach ($categories as $category) {
            ProductCategory::where('id', $category['id'])->update($category);
        }
        $this->clearCache();
    }

    public function bulkDeleteCategories(array $ids)
    {
        ProductCategory::whereIn('id', $ids)->delete();
        $this->clearCache();
    }

    public function getSortedCategories()
    {
        return ProductCategory::orderBy('position')->get();
    }

    public function reorderCategories(array $categoryOrder)
    {
        foreach ($categoryOrder as $position => $id) {
            ProductCategory::where('id', $id)->update(['position' => $position]);
        }
        $this->clearCache();
    }

    public function softDeleteCategory($id)
    {
        $category = ProductCategory::findOrFail($id);
        $category->delete();
        $this->clearCache();
    }

    public function restoreCategory($id)
    {
        $category = ProductCategory::withTrashed()->findOrFail($id);
        $category->restore();
        $this->clearCache();
    }

    public function getCategoryCount()
    {
        return ProductCategory::count();
    }
}