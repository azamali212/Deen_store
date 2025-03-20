<?php
//sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start
namespace App\Repositories\ProductManagement\ProductRepo;

use App\Events\ProductCreated;
use App\Events\ProductCreatedByBundleAndBadge;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\Product;
use App\Models\User;
use App\Models\UserProductView;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;

class ProductRepository implements ProductRepositoryInterface
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();  // Initialize the client here
    }

    // Get all products with pagination
    public function getAllProducts(int $perPage = 10)
    {
        return Product::with(['category', 'subcategory', 'brand', 'variants', 'images'])->paginate($perPage);
    }

    // Get a specific product
    public function getProduct(int $id)
    {
        return Product::with(['category', 'subcategory', 'brand', 'variants', 'images'])->findOrFail($id);
    }

    // Create a new product
    public function createProduct(array $data)
    {
        \Log::info('Product Data Received:', $data); // Log the data to verify input

        $product = Product::create($data);
        event(new ProductCreatedByBundleAndBadge($product));

        if (!$product) {
            \Log::error('Product creation failed.');
            throw new \Exception('Product creation failed.');
        }

        \Log::info('Product Created:', $product->toArray()); // Log the created product
        event(new ProductCreated($product, 'created'));

        return $product;
    }

    // Update an existing product
    public function updateProduct(array $data, int $id)
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        event(new ProductUpdated($product, 'created'));
        return $product;
    }

    // Delete a product
    public function deleteProduct(int $id)
    {
        $product = Product::findOrFail($id);  // Get the product instance first
    
        // Delete the product
        $product->delete();
    
        // Trigger the ProductDeleted event with the product instance
        event(new ProductDeleted($product, 'deleted'));
    
        return $product;
    }

    // Filter products based on various criteria
    public function filterProducts(array $filters, int $perPage = 10)
    {
        $query = Product::query()->with(['category', 'subcategory', 'brand', 'variants', 'images']);

        // Apply filters dynamically
        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (!empty($filters['brand'])) {
            $query->where('brand_id', $filters['brand']);
        }

        if (!empty($filters['price'])) {
            $query->where('price', $filters['price']);
        }

        if (!empty($filters['discount'])) {
            $query->where('discount_price', $filters['discount']);
        }

        if (!empty($filters['stock_quantity'])) {
            $query->where('stock_quantity', $filters['stock_quantity']);
        }

        if (!empty($filters['status'])) {
            $query->where('is_active', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        return $query->paginate($perPage);
    }

    // Get products sorted and paginated
    public function getSortedAndPaginatedProducts(array $filters, string $sortBy = 'created_at', string $sortDirection = 'desc', int $perPage = 10)
    {
        $query = Product::query();

        // Apply filters
        foreach ($filters as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }

        // Apply sorting and pagination
        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    // Get recommended products for a user based on their interactions
  
}
