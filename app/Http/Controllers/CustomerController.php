<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // Fetch all customers
    public function index(): JsonResponse
    {
        try {
            $customers = Customer::all();
            return response()->json([
                'status' => 200, 
                'customers' => $customers
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to fetch customers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($id);
            return response()->json([
                'status' => 200,
                'customer' => $customer,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => "Customer with ID " . $id . " not found",
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while fetching the customer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Store a new customer
    public function store(Request $request): JsonResponse
    {
        $validatedCustomer = $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
        ]);
        
        try {
            $customer = Customer::create($validatedCustomer);
            return response()->json([
                'status' => 201, 
                'message' => 'Customer created successfully',
                'customer' => $customer
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to create customer', 
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validatedCustomer = $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        try {
            $customer = Customer::findOrFail($id);
            $customer->update($validatedCustomer);
            return response()->json([
                'status' => 200, 
                'message' => 'Customer updated successfully',
                'customer' => $customer
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update customer', 
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete($id): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($id);
            $customer->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Customer deleted successfully'
            ],200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Customer not found',
                'error' => $e->getMessage(),
            ],404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete customer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
