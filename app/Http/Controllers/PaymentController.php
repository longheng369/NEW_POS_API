<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Sale;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentController extends Controller
{
    // List all payments
    public function index()
    {
        try {
            $payments = Payment::with(['sale', 'purchase'])->get(); // Eager load the sale relationship
            return response()->json($payments);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve payments', 'error' => $e->getMessage()], 500);
        }
    }

    public function index1()
    {
        try {
            $payments_for_purchase = Payment::with('purchase')
                                ->whereNotNull('purchase_id')
                                ->get();
            return response()->json($payments_for_purchase, 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Show a specific payment
    public function show($id)
    {
        try {
            $payment = Payment::with(['sale', 'purchase'])->findOrFail($id); // Automatically throw an exception if not found
            return response()->json($payment);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Payment not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve payment', 'error' => $e->getMessage()], 500);
        }
    }

    // Create a new payment
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sale_id' => 'nullable|exists:sales,id',
                'purchase_id' => 'nullable|exists:purchases,id',
                'amount' => 'required|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'discount' => 'nullable|numeric|min:0',
                'payment_method' => 'required|string',
                'status' => 'nullable|string|in:pending,completed,canceled', // Allowed statuses
                'transaction_reference' => 'nullable|string',
                'user_id' => 'required|exists:users,id', // Ensure user exists
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $payment = Payment::create($request->all());
            return response()->json($payment, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create payment', 'error' => $e->getMessage()], 500);
        }
    }

    // Update an existing payment
    public function update(Request $request, $id)
    {
        try {
            $payment = Payment::findOrFail($id); // Automatically throw an exception if not found

            $validator = Validator::make($request->all(), [
                'amount' => 'sometimes|numeric|min:0',
                'tax' => 'sometimes|nullable|numeric|min:0',
                'discount' => 'sometimes|nullable|numeric|min:0',
                'payment_method' => 'sometimes|string',
                'status' => 'sometimes|nullable|string|in:pending,completed,canceled',
                'transaction_reference' => 'sometimes|nullable|string',
                'user_id' => 'sometimes|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $payment->update($request->all());
            return response()->json($payment);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Payment not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update payment', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a payment
    public function destroy($id)
    {
        try {
            $payment = Payment::findOrFail($id); // Automatically throw an exception if not found
            $payment->delete();
            return response()->json(['message' => 'Payment deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Payment not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete payment', 'error' => $e->getMessage()], 500);
        }
    }
}
