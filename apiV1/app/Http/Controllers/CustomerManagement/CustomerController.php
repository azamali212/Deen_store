<?php

namespace App\Http\Controllers\CustomerManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerReqeust;
use App\Http\Resources\CustomerResource;
use App\Repositories\CustomerManagement\CustomerRepositoryInterface;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected $customerRepository;
    public function __construct(CustomerRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    // Core CRUD
    public function getAllCustomers()
    {
        $customers = $this->customerRepository->getAllCustomers();
        // Return collection wrapped in resource
        return response()->json([
            'success' => true,
            'data' => CustomerResource::collection($customers),
            'message' => 'Customers retrieved successfully',
        ], 200);
    }

    public function getCustomerById($id)
    {
        $customer = $this->customerRepository->getCustomerById($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        // Return single resource
        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer),
            'message' => 'Customer retrieved successfully',
        ], 200);
    }

    public function createCustomer(CustomerReqeust $request)
    {
        $customer = $this->customerRepository->createCustomer($request->validated());

        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer),
            'message' => 'Customer created successfully',
        ], 201);
    }

    public function updateCustomer(CustomerReqeust $request, $id)
    {
        $customer = $this->customerRepository->updateCustomer($id, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer),
            'message' => 'Customer updated successfully',
        ], 200);
    }

    public function deleteCustomer($id)
    {
        $customer = $this->customerRepository->deleteCustomer($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully',
        ], 200);
    }
}
