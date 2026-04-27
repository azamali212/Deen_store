<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\SupplierRequest;
use App\Http\Resources\Supplier\SupplierResource;
use App\Repositories\SupplierManagement\SupplierRepositoryInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class SupplierController extends Controller
{
    protected $supplierRepository;

    public function __construct(SupplierRepositoryInterface $supplierRepository)
    {
        $this->supplierRepository = $supplierRepository;
    }

    public function store(SupplierRequest $request): JsonResponse
    {
        try {
            // Create supplier using repository
            $supplier = $this->supplierRepository->createSupplier($request->validated());

            // Return successful response
            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully.',
                'data' => new SupplierResource($supplier),
            ], 201);
        } catch (Exception $e) {
            Log::error("Supplier creation failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Failed to create supplier.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(SupplierRequest $request, $id): JsonResponse
    {
        try {
            // Update supplier using repository
            $supplier = $this->supplierRepository->updateSupplier($id, $request->validated());

            // Return successful response
            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully.',
                'data' => new SupplierResource($supplier),
            ], 200);
        } catch (Exception $e) {
            Log::error("Supplier update failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Failed to update supplier.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            // Delete supplier using repository
            $this->supplierRepository->deleteSupplier($id);

            // Return successful response
            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully.',
            ], 200);
        } catch (Exception $e) {
            Log::error("Supplier deletion failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete supplier.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            // Get supplier using repository
            $supplier = $this->supplierRepository->getSupplier($id);

            // Return successful response
            return response()->json([
                'success' => true,
                'data' => new SupplierResource($supplier),
            ], 200);
        } catch (Exception $e) {
            Log::error("Supplier retrieval failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve supplier.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            // Get all suppliers using repository
            $suppliers = $this->supplierRepository->all($request->per_page ?? 10);

            // Return successful response
            return response()->json([
                'success' => true,
                'data' => SupplierResource::collection($suppliers),
            ], 200);
        } catch (Exception $e) {
            Log::error("Supplier retrieval failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve suppliers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function filters(): JsonResponse
    {
        try {
            // Get all suppliers using repository
            $suppliers = $this->supplierRepository->listSuppliers($filters = []);

            // Return successful response
            return response()->json([
                'success' => true,
                'data' => SupplierResource::collection($suppliers),
            ], 200);
        } catch (Exception $e) {
            Log::error("Supplier retrieval failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve suppliers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Other methods
    public function evaluateSupplierPerformance($supplierId): JsonResponse
    {
        try {
            $performanceData = $this->supplierRepository->evaluateSupplierPerformance($supplierId);

            return response()->json([
                'success' => true,
                'message' => 'Supplier performance evaluation successful.',
                'data' => $performanceData,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to evaluate supplier performance.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function terminateContract(int $supplierId): JsonResponse
    {
        try {
            $this->supplierRepository->handleSupplierContractTermination($supplierId);

            return response()->json([
                'success' => true,
                'message' => 'Supplier contract terminated successfully.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate supplier contract.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getTopPerformingSuppliers(int $limit): JsonResponse
    {
        try {
            $topSuppliers = $this->supplierRepository->getTopPerformingSuppliers($limit);

            return response()->json([
                'success' => true,
                'data' => $topSuppliers,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve top performing suppliers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function assignSupplierToCategory(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'category_id' => 'required|exists:supplier_categories,id',
        ]);

        try {
            $result = $this->supplierRepository->assignSupplierToCategory(
                $request->supplier_id,
                $request->category_id
            );

            return response()->json([
                'message' => 'Supplier assigned to category successfully.',
                'success' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'success' => false
            ], 400);
        }
    }

    public function trackSupplierDeliveryStatus(int $supplierId)
    {
        $result = $this->supplierRepository->trackSupplierDeliveryStatus($supplierId);

        return response()->json($result);
    }

    public function generateSupplierReport(Request $request)
    {
        $filters = $request->all();
        $perPage = $request->get('per_page', 20); // Default to 20 if not provided

        return $this->supplierRepository->generateSupplierReport($filters, $perPage);
    }

    public function exportSupplierReport(Request $request)
    {
        $filters = $request->all();
        $filePath = $this->supplierRepository->exportSupplierReport($filters);

        if (!$filePath) {
            return response()->json(['error' => 'Failed to generate report.'], 500);
        }

        return response()->download($filePath);
    }

    public function markSupplierAsPreferred(int $supplierId): JsonResponse
    {
        try {
            $result = $this->supplierRepository->markSupplierAsPreferred($supplierId);

            return response()->json([
                'success' => $result,
                'message' => 'Supplier marked as preferred successfully.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark supplier as preferred.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function checkStockAvailability(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'product_name' => 'required|string'
        ]);

        // Call the repository method correctly
        $available = $this->supplierRepository
            ->checkSupplierStockAvailability($validated['supplier_id'], $validated['product_name']);

        return response()->json([
            'product_available' => $available
        ]);
    }
    /* public function getSupplierPaymentHistory(int $supplierId): JsonResponse
    {
        $paymentHistory = $this->supplierRepository->retrieveSupplierPaymentHistory($supplierId);
        return response()->json($paymentHistory);
    }*/

    public function generateSupplierPaymentHistoryReport(Request $request)
    {
        try {
            // Get filters from request
            $filters = $request->all();
            $perPage = $request->get('per_page', 20);

            // Call the method from the repository
            $reportData = $this->supplierRepository->generateSupplierPaymentHistoryReport($filters, $perPage);

            // If there is no data, return a response
            if ($reportData->isEmpty()) {
                return response()->json(['message' => 'No records found.'], 404);
            }

            // Return the report data as JSON
            return response()->json($reportData);
        } catch (\Throwable $e) {
            // Log error and return a failure response
            \Log::error('Error generating supplier payment history report: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to generate report.'], 500);
        }
    }

    // Export Supplier Payment History Report
    public function exportSupplierPaymentHistoryReport(Request $request)
    {
        try {
            // Get filters from request
            $filters = $request->all();

            // Call the export method from the repository
            $csvFilePath = $this->supplierRepository->exportSupplierPaymentHistoryReport($filters);

            if ($csvFilePath) {
                // Return the file path for downloading
                return response()->download($csvFilePath);
            } else {
                return response()->json(['message' => 'Failed to generate the report.'], 500);
            }
        } catch (\Throwable $e) {
            // Log the error and return a failure response
            \Log::error('Error exporting supplier payment history report: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to export report.'], 500);
        }
    }

    // Blacklist a supplier
    public function blacklistSupplier(int $supplierId, Request $request): JsonResponse
    {
        $reason = $request->input('reason');  // Retrieve 'reason' from request

        // Validate that 'reason' is not empty or null
        if (empty($reason)) {
            return response()->json(['error' => 'Blacklist reason is required'], 400);
        }

        $status = $this->supplierRepository->blacklistSupplier($supplierId, $reason);

        if ($status) {
            return response()->json(['status' => 'Supplier blacklisted successfully']);
        } else {
            return response()->json(['error' => 'Failed to blacklist supplier'], 500);
        }
    }

    // Unblacklist a supplier
    public function unblacklistSupplier(int $supplierId): JsonResponse
    {
        $status = $this->supplierRepository->unblacklistSupplier($supplierId);
        return response()->json(['status' => $status]);
    }

    // Get all blacklisted suppliers
    public function getBlacklistedSuppliers(): JsonResponse
    {
        $suppliers = $this->supplierRepository->getBlacklistedSuppliers();
        return response()->json($suppliers);
    }

    // Process a payment for a supplier
    public function processSupplierPayment(Request $request, $supplierId): JsonResponse
    {
        // Log the supplierId coming from the route
        \Log::info("Supplier ID from URL: {$supplierId}");
    
        // Log the request data (amount and payment method)
        \Log::info("Request Data: " . json_encode($request->all()));
    
        // Get the payment details from the request
        $amount = $request->input('amount');
        $paymentMethod = $request->input('payment_method');
        
        // Make sure the payment date is in the correct format
        $paymentDate = $request->input('payment_date', now());  // Default to current date if not provided
        $paymentDate = Carbon::parse($paymentDate)->format('Y-m-d');  // Ensure it's in Y-m-d format
    
        // Call the repository method to process the payment
        $status = $this->supplierRepository->processSupplierPayment($supplierId, $amount, $paymentMethod, $paymentDate);
    
        // Return the response
        return response()->json(['status' => $status]);
    }

    // Get payment details by payment ID
    public function getSupplierPaymentDetails(int $paymentId): JsonResponse
    {
        $paymentDetails = $this->supplierRepository->getSupplierPaymentDetails($paymentId);
        return response()->json($paymentDetails);
    }

    // Get all suppliers in a specific category
    public function getSuppliersByCategory(int $categoryId): JsonResponse
    {
        $suppliers = $this->supplierRepository->getSuppliersByCategory($categoryId);
        return response()->json($suppliers);
    }

    // Get all suppliers with active contracts
    public function getSuppliersWithActiveContracts(): JsonResponse
    {
        $suppliers = $this->supplierRepository->getSuppliersWithActiveContracts();
        return response()->json($suppliers);
    }

    // Update the contract status of a supplier
    public function updateSupplierContractStatus(Request $request, int $supplierId): JsonResponse
    {
        $status = $request->input('status');
        $updated = $this->supplierRepository->updateSupplierContractStatus($supplierId, $status);
        return response()->json(['updated' => $updated]);
    }

    // Get all suppliers with pending contracts
    public function getSuppliersPendingContracts(): JsonResponse
    {
        $suppliers = $this->supplierRepository->getSuppliersPendingContracts();
        return response()->json($suppliers);
    }

    // Get all suppliers with terminated contracts
    public function getContractTerminatedSuppliers(): JsonResponse
    {
        $suppliers = $this->supplierRepository->getContractTerminatedSuppliers();
        return response()->json($suppliers);
    }
}
