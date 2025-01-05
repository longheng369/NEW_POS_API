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
                'status' => 'nullable|string|in:pending,completed,cancelled', // Allowed statuses
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
    public function updatePayment(Request $request, $id)
    {
        $validated = $request->validate([
            'payments' => 'required|array',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.payment_method' => 'required|string|in:cash,credit,aba', // added 'aba' payment method
            'payments.*.payment_date' => 'nullable|date',
            'payments.*.user_id' => 'required|exists:users,id',
            'payments.*.payment_id' => 'nullable|exists:payments,id', // Optional, for updating existing payments
        ]);

        DB::beginTransaction();

        try {
            // Find the existing purchase record
            $purchase = Purchase::findOrFail($id);

            // Calculate the total paid amount so far
            $totalPaid = $purchase->payments()->sum('amount');

            // Calculate the total amount to be paid with the new payments
            $newPaymentsTotal = array_sum(array_column($validated['payments'], 'amount'));

            // Ensure the new payments do not exceed the total debt
            if ($totalPaid + $newPaymentsTotal > $purchase->grand_total) {
                throw new Exception('The total payment amount exceeds the purchase debt.');
            }

            // Iterate over each payment and process accordingly
            foreach ($validated['payments'] as $paymentData) {
                if (isset($paymentData['payment_id'])) {
                    // If payment_id exists, update the existing payment method
                    $payment = Payment::findOrFail($paymentData['payment_id']);
                    $payment->update([
                        'payment_method' => $paymentData['payment_method'],
                        'amount' => $paymentData['amount'],
                        'payment_date' => $paymentData['payment_date'] ?? $payment->payment_date,
                    ]);
                } else {
                    // If no payment_id, create a new payment
                    $purchase->payments()->create([
                        'amount' => $paymentData['amount'],
                        'payment_method' => $paymentData['payment_method'],
                        'payment_date' => $paymentData['payment_date'],
                        'user_id' => $paymentData['user_id'],
                    ]);
                }
            }

            // Recalculate total paid after adding or updating payments
            $totalPaid = $purchase->payments()->sum('amount');

            // Update the total paid amount on the purchase
            $purchase->total_paid = $totalPaid;
            $purchase->save();

            DB::commit();

            return response()->json([
                'message' => 'Payments updated successfully',
                'data' => $purchase->payments,
                'total_paid' => $totalPaid,
                'grand_total' => $purchase->grand_total,
                'newPaymentsTotal' => $newPaymentsTotal,
                'total_payment' => $totalPaid + $newPaymentsTotal,
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update payments', 'error' => $e->getMessage()], 500);
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
