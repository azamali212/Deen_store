<?php

namespace App\Repositories\SupplierManagement;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierRepository implements SupplierRepositoryInterface
{

    public function createSupplier(array $data): Supplier
    {
        DB::beginTransaction();

        try {
            $supplier = Supplier::create($data);

            if (!$supplier) {
                throw new Exception('Failed to create supplier.');
            }

            DB::commit();
            Log::info("Supplier created: {$supplier->id}");

            return $supplier;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error creating supplier: {$e->getMessage()}");
            throw new Exception('Supplier creation failed.');
        }
    }

    public function updateSupplier(int $supplierId, array $data): Supplier
    {
        $supplier = Supplier::findOrFail($supplierId);

        DB::beginTransaction();

        try {
            $supplier->update($data);
            DB::commit();
            Log::info("Supplier updated: {$supplier->id}");

            return $supplier;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error updating supplier: {$e->getMessage()}");
            throw new Exception('Supplier update failed.');
        }
    }

    public function deleteSupplier(int $supplierId): bool
    {
        $supplier = Supplier::findOrFail($supplierId);

        return $supplier->delete();
    }

    public function getSupplier(int $supplierId): Supplier
    {
        return Supplier::findOrFail($supplierId);
    }

    public function all(int $perPage = 10)
    {
        return Supplier::paginate($perPage);
    }

    public function listSuppliers(array $filters = []): Collection
    {
        return Supplier::query()->filter($filters)->get();
    }

    public function updateSupplierProductPrice(int $supplierId, float $newPrice) {}

    public function evaluateSupplierPerformance(int $supplierId): array
    {
        // Get the supplier with related data (purchase orders, payments, etc.)
        $supplier = Supplier::with(['purchaseOrders', 'payments', 'categories'])->findOrFail($supplierId);

        if (!$supplier) {
            throw new Exception("Supplier not found.");
        }

        // Get total number of orders and successful deliveries
        $totalOrders = $supplier->purchaseOrders()->count();
        $onTimeOrders = $supplier->purchaseOrders()->whereColumn('delivered_at', '<=', 'expected_delivery')->count();
        $onTimeDeliveryRate = $totalOrders > 0 ? round(($onTimeOrders / $totalOrders) * 100, 2) : 0;

        // Get total payments and late payments
        $totalPayments = $supplier->payments()->count();
        $latePayments = $supplier->payments()->where('status', 'late')->count();
        $onTimePaymentRate = $totalPayments > 0 ? round((($totalPayments - $latePayments) / $totalPayments) * 100, 2) : 100;

        // Calculate average performance rating from past purchase orders
        $averageRating = $supplier->purchaseOrders()->avg('rating') ?? 0;

        // Blacklist and contract status check
        $blacklisted = $supplier->blacklisted ? 'Yes' : 'No';
        $contractStatus = $supplier->contract_status ?? 'Inactive';

        // Supplier Category analysis (considering category performance impact)
        $categoryName = $supplier->supplierCategory->name ?? 'Unknown Category';

        // Performance Rating Calculation
        $performanceRating = ($onTimeDeliveryRate * 0.3) + ($onTimePaymentRate * 0.3) + ($averageRating * 0.2);

        // Penalty for being blacklisted
        if ($blacklisted === 'Yes') {
            $performanceRating -= 10; // Penalty for blacklist
        }

        // If the supplier has an inactive contract, reduce their score
        if ($contractStatus === 'Inactive') {
            $performanceRating -= 5; // Penalty for inactive contract
        }

        // Ensure the performance rating stays within the 0-100 range
        $performanceRating = max(0, min(100, $performanceRating));

        return [
            'supplier_id' => $supplier->id,
            'name' => $supplier->name,
            'on_time_delivery_rate' => $onTimeDeliveryRate . '%',
            'on_time_payment_rate' => $onTimePaymentRate . '%',
            'average_rating' => round($averageRating, 2),
            'blacklisted' => $blacklisted,
            'contract_status' => $contractStatus,
            'category_name' => $categoryName,
            'final_performance_rating' => round($performanceRating, 2) . ' / 100',
        ];
    }

    public function handleSupplierContractTermination(int $supplierId): bool
    {
        // Check if the supplier exists and is active
        $supplier = Supplier::find($supplierId);

        if (!$supplier) {
            throw new Exception("Supplier not found.");
        }

        if ($supplier->contract_status !== 'active') {
            throw new Exception("Supplier contract is not active.");
        }

        // Update the supplier's contract status to terminated
        $supplier->contract_status = 'terminated';
        $supplier->save();

        // Handle any related data, like cancelling pending orders
        $supplier->purchaseOrders()->where('status', 'pending')->update(['status' => 'cancelled']);

        // Optionally, send a notification to the supplier about the contract termination
        //Notification::send($supplier, new SupplierContractTerminationNotification($supplier));

        return true;
    }

    public function getTopPerformingSuppliers(int $limit): Collection
    {
        return Supplier::with(['purchaseOrders', 'payments'])
            ->where('contract_status', 'active')
            ->where('blacklisted', false)
            ->select('suppliers.*')
            ->addSelect([
                DB::raw('(SELECT AVG(purchase_orders.rating) FROM purchase_orders WHERE purchase_orders.supplier_id = suppliers.id) AS average_rating'),

                DB::raw('(SELECT 
                            (COUNT(*) * 100) / NULLIF((SELECT COUNT(*) FROM purchase_orders WHERE purchase_orders.supplier_id = suppliers.id), 0)
                          FROM purchase_orders 
                          WHERE purchase_orders.supplier_id = suppliers.id 
                          AND purchase_orders.delivered_at <= purchase_orders.expected_delivery
                        ) AS on_time_delivery_rate'),

                DB::raw('(SELECT 
                            (COUNT(*) * 100) / NULLIF((SELECT COUNT(*) FROM supplier_payments WHERE supplier_payments.supplier_id = suppliers.id), 0)
                          FROM supplier_payments 
                          WHERE supplier_payments.supplier_id = suppliers.id 
                          AND supplier_payments.status = "on_time"
                        ) AS on_time_payment_rate'),
            ])
            ->orderByDesc('average_rating')
            ->orderByDesc('on_time_delivery_rate')
            ->orderByDesc('on_time_payment_rate')
            ->limit($limit)
            ->get()
            ->map(function ($supplier) {
                $performanceScore = $this->calculatePerformanceScore($supplier);
                $supplier->performance_score = $performanceScore;
                return $supplier;
            });
    }
    private function calculatePerformanceScore(Supplier $supplier): float
    {
        $deliveryRate = $supplier->on_time_delivery_rate ?? 0;
        $paymentRate = $supplier->on_time_payment_rate ?? 0;
        $rating = $supplier->average_rating ?? 0;

        // Define the weightings for each factor (can be adjusted based on business requirements)
        $deliveryWeight = 0.4;
        $paymentWeight = 0.3;
        $ratingWeight = 0.3;

        // Calculate the performance score using weighted average
        $performanceScore = ($deliveryRate * $deliveryWeight) + ($paymentRate * $paymentWeight) + ($rating * $ratingWeight);

        return $performanceScore;
    }

    public function assignSupplierToCategory(int $supplierId, int $categoryId): bool
    {
        try {
            // Check if the supplier exists
            $supplier = Supplier::findOrFail($supplierId);

            // Check if the category exists
            $category = SupplierCategory::findOrFail($categoryId);

            // Check if the supplier is already assigned to this category
            if ($supplier->supplier_category_id == $categoryId) {
                throw new Exception('Supplier is already assigned to this category.');
            }

            // Assign the supplier to the category (update supplier's category)
            $supplier->supplier_category_id = $categoryId;
            $supplier->save();

            // Log the success
            Log::info("Supplier {$supplier->name} has been successfully assigned to category {$category->name}.");

            return true;
        } catch (ModelNotFoundException $e) {
            Log::error("Supplier or Category not found: " . $e->getMessage());
            throw new Exception('Supplier or Category not found.');
        } catch (Exception $e) {
            Log::error("Error assigning supplier to category: " . $e->getMessage());
            throw new Exception('Error assigning supplier to category.');
        }
    }

    //More and More Advance After 
    public function trackSupplierDeliveryStatus(int $supplierId): array
    {
        try {
            // Retrieve the supplier
            $supplier = Supplier::findOrFail($supplierId);

            // Get the supplier's purchase orders and their status, delivery date, and expected delivery date
            $purchaseOrders = $supplier->purchaseOrders()
                ->select('id', 'order_number', 'status', 'delivered_at', 'expected_delivery')
                ->get();

            // Check if there are no purchase orders
            if ($purchaseOrders->isEmpty()) {
                return [
                    'message' => 'No purchase orders found for this supplier.',
                    'status' => false,
                ];
            }

            // Format the results
            $orderStatuses = $purchaseOrders->map(function ($order) {
                return [
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'expected_delivery' => $order->expected_delivery,
                    'delivered_at' => $order->delivered_at,
                    'delivery_status' => $this->getDeliveryStatusLabel($order),
                ];
            });

            return [
                'status' => true,
                'supplier_name' => $supplier->name,
                'order_statuses' => $orderStatuses,
            ];
        } catch (ModelNotFoundException $e) {
            // Log error and return failure
            Log::error("Supplier not found: " . $e->getMessage());
            return [
                'message' => 'Supplier not found.',
                'status' => false,
            ];
        } catch (Exception $e) {
            // Log any other exceptions and return failure
            Log::error("Error tracking delivery status: " . $e->getMessage());
            return [
                'message' => 'Error tracking delivery status.',
                'status' => false,
            ];
        }
    }


    private function getDeliveryStatusLabel(PurchaseOrder $order): string
    {
        if ($order->status === 'delivered' && $order->delivered_at !== null) {
            return 'Delivered on ' . $order->delivered_at->format('Y-m-d');
        }

        if ($order->status === 'shipped' && $order->expected_delivery) {
            return 'Shipped, expected delivery on ' . $order->expected_delivery->format('Y-m-d');
        }

        if ($order->status === 'pending') {
            return 'Pending delivery';
        }

        return 'Unknown status';
    }

    public function generateSupplierReport(array $filters, int $perPage = 20)
    {
        try {
            // Log the filters and perPage value for debugging
            \Log::info('Filters:', $filters);
            \Log::info('PerPage:', [$perPage]);

            // Get the current month and year (or use from the filters)
            $currentMonth = isset($filters['month']) ? (int) $filters['month'] : Carbon::now()->month;
            $currentYear = isset($filters['year']) ? (int) $filters['year'] : Carbon::now()->year;

            return Cache::remember("supplier_report_" . md5(json_encode($filters)), 600, function () use ($filters, $currentMonth, $currentYear, $perPage) {
                $query = Supplier::with(['purchaseOrders', 'payments'])
                    ->when(!empty($filters['supplier_id']), fn($q) => $q->where('id', $filters['supplier_id']))
                    ->when(!empty($filters['name']), fn($q) => $q->where('name', 'like', '%' . $filters['name'] . '%'))
                    ->when(!empty($filters['month']), fn($q) => $q->whereMonth('created_at', $currentMonth))
                    ->when(!empty($filters['year']), fn($q) => $q->whereYear('created_at', $currentYear));

                $results = $query->paginate($perPage);

                if ($results->isEmpty()) {
                    \Log::info('No results found for the given filters.');
                    return response()->json(['message' => 'No records found.'], 404);
                }

                return $results;
            });
        } catch (\Throwable $e) {
            \Log::error('Error generating supplier report: ' . $e->getMessage(), [
                'filters' => $filters,
                'exception' => $e
            ]);
            return response()->json(['error' => 'Unable to generate report.'], 500);
        }
    }
    public function exportSupplierReport(array $filters)
    {
        try {
            \Log::info('Exporting supplier report with filters: ', $filters);

            // Generate the report data
            $reportData = $this->generateSupplierReport($filters, 1000);

            // Log if no data is returned
            if ($reportData->isEmpty()) {
                \Log::warning('No data found for the given filters');
                throw new Exception('No data found for the report.');
            }

            $csvFileName = 'supplier_report_' . now()->format('Ymd_His') . '.csv';
            $csvFilePath = storage_path('app/reports/' . $csvFileName);

            // Ensure the directory exists and is writable
            $directoryPath = storage_path('app/reports');
            if (!is_dir($directoryPath)) {
                \Log::info('Creating directory: ' . $directoryPath);
                mkdir($directoryPath, 0775, true);  // Create the directory if it doesn't exist
            }

            // Open file for writing
            $file = fopen($csvFilePath, 'w');
            if (!$file) {
                throw new Exception('Failed to open file for writing.');
            }

            // Add header row
            fputcsv($file, ['Supplier Name', 'Total Orders', 'Total Order Value', 'Total Payments Made', 'Performance Rating', 'Report Generated On']);

            // Write the data rows to the CSV file
            foreach ($reportData as $supplier) {
                $purchaseOrders = $supplier->purchaseOrders()
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->get();
                $payments = $supplier->payments()
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->get();

                $totalOrderValue = $purchaseOrders->sum('total_cost');
                $totalPaymentsMade = $payments->sum('amount');

                fputcsv($file, [
                    $supplier->name,
                    $purchaseOrders->count(),
                    $totalOrderValue,
                    $totalPaymentsMade,
                    $supplier->performance_rating ?? 'N/A',
                    Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);

            // Log successful export
            \Log::info("Report exported successfully to: " . $csvFilePath);

            // Return the file path for download
            return $csvFilePath;
        } catch (Exception $e) {
            // Log the exception for better debugging
            \Log::error('Error in exportSupplierReport: ' . $e->getMessage());
            return false; // Return false if export fails
        }
    }

    public function markSupplierAsPreferred(int $supplierId): bool
    {
        $supplier = Supplier::findOrFail($supplierId);

        $supplier->is_preferred = true;
        return $supplier->save();
    }

    public function checkSupplierStockAvailability(int $supplierId, string $productName): bool
    {
        $cacheKey = "supplier_{$supplierId}_product_" . md5(strtolower($productName));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($supplierId, $productName) {
            Log::info("Checking stock for product: '{$productName}' with supplier ID: {$supplierId}");

            // Perform the query with case-insensitive name matching
            $product = Product::where('supplier_id', $supplierId)
                ->whereRaw('LOWER(name) = ?', [strtolower($productName)]) // Case-insensitive search
                ->where('stock_quantity', '>', 0) // Ensure stock is available
                ->first();

            if (!$product) {
                Log::warning("Product '{$productName}' not found or out of stock for supplier ID {$supplierId}");
                return false;
            }

            Log::info("Product '{$productName}' is available in stock for supplier ID {$supplierId}");
            return true;
        });
    }

    public function generateSupplierPaymentHistoryReport(array $filters, int $perPage = 20)
    {
        try {
            \Log::info('Filters:', $filters);
            \Log::info('PerPage:', [$perPage]);

            // Get the current month and year (or use from the filters)
            $currentMonth = isset($filters['month']) ? (int) $filters['month'] : Carbon::now()->month;
            $currentYear = isset($filters['year']) ? (int) $filters['year'] : Carbon::now()->year;

            return Cache::remember("supplier_payment_history_report_" . md5(json_encode($filters)), 600, function () use ($filters, $currentMonth, $currentYear, $perPage) {
                $query = Supplier::with(['payments'])
                    ->when(!empty($filters['supplier_id']), fn($q) => $q->where('id', $filters['supplier_id']))
                    ->when(!empty($filters['month']), fn($q) => $q->whereMonth('payments.created_at', $currentMonth))
                    ->when(!empty($filters['year']), fn($q) => $q->whereYear('payments.created_at', $currentYear));

                $results = $query->paginate($perPage);

                if ($results->isEmpty()) {
                    \Log::info('No results found for the given filters.');
                    return response()->json(['message' => 'No records found.'], 404);
                }

                return $results;
            });
        } catch (\Throwable $e) {
            \Log::error('Error generating payment history report: ' . $e->getMessage(), [
                'filters' => $filters,
                'exception' => $e
            ]);
            return response()->json(['error' => 'Unable to generate report.'], 500);
        }
    }
    public function exportSupplierPaymentHistoryReport(array $filters)
    {
        try {
            \Log::info('Exporting payment history report with filters: ', $filters);

            // Generate the report data
            $reportData = $this->generateSupplierPaymentHistoryReport($filters, 1000);

            // Log if no data is returned
            if ($reportData->isEmpty()) {
                \Log::warning('No data found for the given filters');
                throw new Exception('No data found for the report.');
            }

            // Define CSV file name and path
            $csvFileName = 'payment_history_report_' . now()->format('Ymd_His') . '.csv';
            $csvFilePath = storage_path('app/reports/' . $csvFileName);

            // Ensure the directory exists and is writable
            $directoryPath = storage_path('app/reports');
            if (!is_dir($directoryPath)) {
                \Log::info('Creating directory: ' . $directoryPath);
                mkdir($directoryPath, 0775, true);  // Create the directory if it doesn't exist
            }

            // Open file for writing
            $file = fopen($csvFilePath, 'w');
            if (!$file) {
                throw new Exception('Failed to open file for writing.');
            }

            // Add header row
            fputcsv($file, ['Supplier Name', 'Payment ID', 'Amount', 'Payment Method', 'Status', 'Payment Date']);

            // Write the data rows to the CSV file
            foreach ($reportData as $supplier) {
                $payments = $supplier->payments()
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->get();

                foreach ($payments as $payment) {
                    fputcsv($file, [
                        $supplier->name,
                        $payment->id,
                        $payment->amount,
                        $payment->payment_method,
                        $payment->status,
                        $payment->created_at->toDateTimeString(),
                    ]);
                }
            }

            fclose($file);

            // Log successful export
            \Log::info("Report exported successfully to: " . $csvFilePath);

            // Return the file path for download
            return $csvFilePath;
        } catch (Exception $e) {
            // Log the exception for better debugging
            \Log::error('Error in exportSupplierPaymentHistoryReport: ' . $e->getMessage());
            return false; // Return false if export fails
        }
    }
   /* public function retrieveSupplierPaymentHistory(int $supplierId): array
    {
        $supplier = Supplier::findOrFail($supplierId);

        // You can add additional logic like filtering by a date range
        return $supplier->payments()->orderByDesc('created_at')->get()->toArray();
    }*/


    public function blacklistSupplier(int $supplierId, string $reason): bool
    {
        $supplier = Supplier::findOrFail($supplierId);

        // Begin transaction to ensure data consistency
        DB::beginTransaction();

        try {
            $supplier->blacklisted = true;
            $supplier->blacklist_reason = $reason;
            $supplier->save();

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            // Log the error
            \Log::error("Failed to blacklist supplier {$supplierId}: {$e->getMessage()}");
            return false;
        }
    }

    public function unblacklistSupplier(int $supplierId): bool
    {
        $supplier = Supplier::findOrFail($supplierId);

        // Begin transaction to ensure data consistency
        DB::beginTransaction();

        try {
            $supplier->blacklisted = false;
            $supplier->blacklist_reason = null;
            $supplier->save();

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error("Failed to unblacklist supplier {$supplierId}: {$e->getMessage()}");
            return false;
        }
    }

    // Get all blacklisted suppliers
    public function getBlacklistedSuppliers(): Collection
    {
        return Supplier::where('blacklisted', true)->get();
    }

    public function processSupplierPayment(int $supplierId, float $amount, string $paymentMethod, string $paymentDate): bool
    {
        $supplier = Supplier::findOrFail($supplierId);
        
        // Log the supplier ID and the payment details
        \Log::info("Supplier found: {$supplier->id}");
        
        // Begin transaction to ensure data consistency
        DB::beginTransaction();
        
        try {
            // Log the payment creation attempt
            \Log::info("Attempting to create payment for supplier {$supplierId} with amount {$amount}");
    
            // Create a new payment entry and set the payment_date
            $payment = new SupplierPayment([
                'supplier_id' => $supplierId,  // Explicitly set supplier_id
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'status' => 'on_time',
                'payment_date' => $paymentDate,  // Set the provided payment_date
            ]);
    
            // Log the supplier ID that will be saved
            \Log::info("Supplier ID set in payment: {$payment->supplier_id}");
    
            $payment->save();
    
            DB::commit();
    
            // Log successful payment processing
            \Log::info("Payment processed successfully for supplier {$supplierId}, amount: {$amount}");
    
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            // Log the error
            \Log::error("Failed to process payment for supplier {$supplierId}: {$e->getMessage()}");
            return false;
        }
    }
    public function getSupplierPaymentDetails(int $paymentId): array
    {
        $payment = SupplierPayment::findOrFail($paymentId);

        // You can customize this to include related data like payment method, etc.
        return $payment->toArray();
    }

    // Get all suppliers in a specific category
    public function getSuppliersByCategory(int $categoryId): Collection
    {
        return Supplier::where('supplier_category_id', $categoryId)->get();
    }

    // Get all suppliers with active contracts
    public function getSuppliersWithActiveContracts(): Collection
    {
        return Supplier::where('contract_status', 'active')->get();
    }

    // Update the contract status of a supplier
    public function updateSupplierContractStatus(int $supplierId, string $status): bool
    {
        $supplier = Supplier::findOrFail($supplierId);

        $supplier->contract_status = $status;

        return $supplier->save();
    }

    // Get all suppliers with pending contracts
    public function getSuppliersPendingContracts(): Collection
    {
        return Supplier::where('contract_status', 'pending')->get();
    }

    // Get all suppliers with terminated contracts
    public function getContractTerminatedSuppliers(): Collection
    {
        return Supplier::where('contract_status', 'terminated')->get();
    }
}
