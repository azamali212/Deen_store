<?php 
namespace App\Repositories\CategoryManagement\ParentCategoryRepo;

interface CategoryRepositoryInterface{
    // Basic CRUD Operations
    public function getAllCategories();
    public function getCategoryById($id);
    public function createCategory(array $data);
    public function updateCategory($id, array $data);
    public function deleteCategory($id);

     // Advanced Fetching Methods
     public function getActiveCategories(); // Fetch only active categories
     public function getParentCategories(); // Fetch only top-level categories
     public function getCategoriesWithSubcategories(); // Optimized query with eager loading
     public function getCategoryBySlug(string $slug); // Get category by slug
     public function searchCategories(string $keyword); // Full-text search for categories

     // Caching Methods
    public function clearCache(); // Clear category cache manually

    // Bulk Operations
     //Insert multiple categories in a single query.
    //Bulk is a opertaion which is just single query to run whole code, allow the same operation to be run on one or more resources within a single request
    public function bulkCreateCategories(array $categories); // Bulk insert multiple categories
    public function bulkUpdateCategories(array $categories); // Bulk update multiple categories
    public function bulkDeleteCategories(array $ids); // Bulk delete multiple categories

    // Real-time Updates (WebSockets, Broadcasting)
    //public function broadcastCategoryCreated($category);
    //public function broadcastCategoryUpdated($category);
    //public function broadcastCategoryDeleted($category);

     // Sorting and Positioning
     public function getSortedCategories(); // Fetch categories sorted by position
     public function reorderCategories(array $categoryOrder); // Update category sorting order

      // Soft Deletes and Restoring
    public function softDeleteCategory($id); // Soft delete a category
    public function restoreCategory($id); // Restore a soft-deleted category

    // Count and Statistics
    public function getCategoryCount(); // Get the total number of categories

}