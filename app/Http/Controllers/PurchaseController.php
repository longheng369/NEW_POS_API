<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Unit;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Variant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\UnitConversionService;
use App\Services\GenerateReferenceNumber;

class PurchaseController extends Controller
{
    //
    protected $unitConversionService;

    protected $generateReferenceNumber;

    public function __construct(UnitConversionService $unitConversionService, GenerateReferenceNumber $generateReferenceNumber)
    {
        $this->unitConversionService = $unitConversionService;
        $this->generateReferenceNumber = $generateReferenceNumber;
    }

    public function index()
    {
        $purchases = Purchase::with(['supplier:id,name', 'user:id,name', 'payments'])
            ->get()
            ->map(function ($purchase) {
                // Calculate total payment
                $totalPaid = $purchase->payments->sum('amount');
                $grandTotal = $purchase->grand_total;
                $balance = $grandTotal - $totalPaid;

                // Determine payment status
                $paymentStatus = 'due';
                if ($totalPaid > 0 && $totalPaid < $grandTotal) {
                    $paymentStatus = 'partial';
                } elseif ($totalPaid == $grandTotal) {
                    $paymentStatus = 'complete';
                }

                return [
                    'id' => $purchase->id,
                    'supplier_name' => $purchase->supplier->name,
                    'reference_number' => $purchase->reference_number,
                    'date_of_purchase' => $purchase->date,
                    'payment_status' => $paymentStatus,
                    'payment_methods' => $purchase->payments->pluck('payment_method')->unique()->values(),
                    'user_name' => $purchase->user->name,
                    'grand_total' => $grandTotal,
                    'money_paid' => $totalPaid,
                    'balance' => $balance,
                    'purchase_status' => $purchase->status
                ];
            });

        return response()->json(['data' => $purchases]);
    }

    public function show($id)
    {
        $purchase = Purchase::with(['supplier', 'user', 'payments', 'items'])->findOrFail($id);
        return response()->json(['data' => $purchase]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateStoreRequest($request);

        $validated['date'] = $validated['date'] ?? now()->toDateString();

        $validated['reference_number'] = $this->generateReferenceNumber->generateReferenceNumber('PUR');

        DB::beginTransaction();

        try {
            // Create the purchase record
            $purchase = Purchase::create($validated);

            $total = 0;

            // Process items and calculate subtotals
            foreach ($validated['items'] as $item) {
                // Process individual purchase item
                $subtotal = $this->processPurchaseItem($item, $purchase);

                // Update the total
                $total += $subtotal;
            }

            // Update the grand total in the purchase record
            $purchase->grand_total = $total;
            $purchase->save();

            // Handle payments
            if (isset($validated['payments'])) {
                $this->storePayments($validated['payments'], $validated['user_id'], $purchase);
            }

            DB::commit();

            return response()->json(
                [
                    'message' => 'Purchase created successfully',
                    'data' => $purchase,
                ],
                201,
            );
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create purchase', 'error' => $e->getMessage()], 500);
        }
    }

    private function processPurchaseItem(array $item, Purchase $purchase)
    {
        // Fetch variant, product, and unit
        $variant = Variant::where('id', $item['variant_id'])
            ->where('product_id', $item['product_id'])
            ->firstOrFail();

        $product = Product::findOrFail($item['product_id']);
        $unit = Unit::findOrFail($item['unit_id']);

        // Convert quantity to base unit
        $quantityInBaseUnit = $this->unitConversionService->convertToBaseUnit($product->id, $item['unit_id'], $item['quantity']);

        // Calculate prices
        $priceData = $this->calculatePrice($item, $variant, $product, $unit);

        // Calculate subtotal
        $discount = $item['discount'] ?? 0;
        $subtotal = $quantityInBaseUnit * $priceData['unit_price'] * (1 - $discount / 100);

        // Prepare purchase item data
        $purchaseItemData = [
            'purchase_id' => $purchase->id,
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'],
            'unit_id' => $item['unit_id'],
            'quantity' => $quantityInBaseUnit,
            'unit_price' => $priceData['unit_price'],
            'discount' => $discount,
            'subtotal' => $subtotal,
            'price_per_piece' => $priceData['price_per_piece'],
            'expiration_date' => $item['expiration_date'] ?? null,
            'batch_number' => $item['batch_number'] ?? null,
        ];

        // Create purchase item
        PurchaseItem::create($purchaseItemData);

        // Update stock for the variant
        $variant->stock += $quantityInBaseUnit;
        $variant->save();

        return $subtotal;
    }

    private function calculatePrice(array $item, Variant $variant, Product $product, Unit $unit)
    {
        // Default price_per_piece and unit_price
        if (isset($item['unit_price']) && $item['unit_price'] !== null) {
            $price_per_piece = $variant->costing;
            $unit_price = $item['unit_price']; // Use user-provided value
        } elseif ($product->base_unit_id === $item['unit_id']) {
            $price_per_piece = $variant->costing;
            $unit_price = $variant->costing;
        } elseif ($product->unit_id === $item['unit_id']) {
            $conversionFactor = $product->conversion_factor ?: 1;
            $price_per_piece = $variant->costing;
            $unit_price = $variant->costing * $conversionFactor;
        } elseif ($unit->conversion_factor > 0) {
            $price_per_piece = $variant->costing / $unit->conversion_factor;
            $unit_price = $variant->costing * $unit->conversion_factor;
        } else {
            // Fallback to costing if no explicit unit_price is provided or calculated
            $unit_price = $variant->costing;
            $price_per_piece = $variant->costing;
        }

        // If `unit_price` was not set explicitly, fallback to calculated price
        if (empty($unit_price)) {
            $unit_price = $price_per_piece;
        }

        return ['unit_price' => $unit_price, 'price_per_piece' => $price_per_piece];
    }

    private function storePayments(array $payments, int $userId, Purchase $purchase)
    {
        foreach ($payments as &$payment) {
            $payment['user_id'] = $userId;
            $payment['purchase_id'] = $purchase->id;
            $payment['status'] = $payment['status'] ?? 'pending';
            $payment['payment_date'] = $payment['payment_date'] ?? now();
        }

        try {
            // Create multiple payments associated with the purchase
            $purchase->payments()->createMany($payments);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error storing payments: ' . $e->getMessage(), ['payments' => $payments]);
            throw new \Exception('Failed to store payments');
        }
    }

    private function validateStoreRequest(Request $request)
    {
        return $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'user_id' => 'required|exists:users,id',
            'tax_rate' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:pending,completed,canceled',
            'notes' => 'nullable|string|max:500',
            'date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:variants,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
            'items.*.expiration_date' => 'nullable|date|after_or_equal:today',
            'items.*.batch_number' => 'nullable|string|max:255',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'required_if:payments,true|numeric|min:0',
            'payments.*.payment_method' => 'required_if:payments,true|string|in:cash,credit',
            'payments.*.payment_date' => 'nullable|date',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validateUpdateRequest($request);

        DB::beginTransaction();

        try {
            $purchase = $this->updatePurchase($id, $validated);

            if (isset($validated['items'])) {
                $this->updatePurchaseItems($purchase, $validated['items']);
            }

            if (isset($validated['payments'])) {
                $this->updatePurchasePayments($purchase, $validated['payments']);
            }

            DB::commit();

            return response()->json(
                [
                    'message' => 'Purchase updated successfully',
                    'data' => $purchase->load('items', 'payments'), // Load related data
                ],
                200,
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Purchase update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update purchase', 'error' => $e->getMessage()], 500);
        }
    }

    private function validateUpdateRequest(Request $request)
    {
        return $request->validate([
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'tax_rate' => 'sometimes|nullable|numeric|min:0',
            'discount' => 'sometimes|nullable|numeric|min:0',
            'status' => 'sometimes|nullable|string|in:pending,completed,canceled',
            'notes' => 'sometimes|nullable|string|max:500',
            'date' => 'sometimes|nullable|date',
            'items' => 'sometimes|required|array|min:1',
            'items.*.variant_id' => 'sometimes|required|exists:variants,id',
            'items.*.product_id' => 'sometimes|required|exists:products,id',
            'items.*.unit_id' => 'sometimes|required|exists:units,id',
            'items.*.quantity' => 'sometimes|required|integer|min:1',
            'items.*.unit_price' => 'sometimes|nullable|numeric|min:0',
            'items.*.discount' => 'sometimes|nullable|numeric|min:0|max:100',
            'items.*.expiration_date' => 'sometimes|nullable|date|after_or_equal:today',
            'items.*.batch_number' => 'sometimes|nullable|string|max:255',
            'payments' => 'sometimes|nullable|array',
            'payments.*.amount' => 'sometimes|required|numeric|min:0',
            'payments.*.payment_method' => 'sometimes|required|string|in:cash,credit,aba',
            'payments.*.payment_date' => 'sometimes|nullable|date',
            'payments.*.user_id' => 'sometimes|required|exists:users,id',
            'payments.*.payment_id' => 'nullable|exists:payments,id',
        ]);
    }

    private function updatePurchase($id, $validated)
    {
        $purchase = Purchase::findOrFail($id);
        $purchase->update($validated);
        return $purchase;
    }

    private function updatePurchaseItems($purchase, array $items)
    {
        $total = 0;

        // Revert stock for existing items
        foreach ($purchase->items as $existingItem) {
            $variant = Variant::find($existingItem->variant_id);
            $variant->stock -= $existingItem->quantity; // Subtract previous stock
            $variant->save();
        }

        // Delete existing purchase items
        $purchase->items()->delete();

        foreach ($items as $item) {
            $variant = Variant::where('id', $item['variant_id'])
                ->where('product_id', $item['product_id'])
                ->firstOrFail();

            $product = Product::findOrFail($item['product_id']);
            $unit = Unit::findOrFail($item['unit_id']);

            $quantityInBaseUnit = $this->unitConversionService->convertToBaseUnit($product->id, $item['unit_id'], $item['quantity']);

            $price_per_piece = $variant->costing;
            $unit_price = $item['unit_price'] ?? $variant->costing;

            $discount = $item['discount'] ?? 0;
            $quantity = $item['quantity'];
            $subtotal = $quantity * $unit_price * (1 - $discount / 100);

            $purchaseItemData = [
                'purchase_id' => $purchase->id,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $quantityInBaseUnit,
                'unit_price' => $unit_price,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'price_per_piece' => $price_per_piece,
                'expiration_date' => $item['expiration_date'] ?? null,
                'batch_number' => $item['batch_number'] ?? null,
            ];

            PurchaseItem::create($purchaseItemData);
            $total += $subtotal;

            // Update stock
            $variant->stock += $quantityInBaseUnit;
            $variant->save();
        }

        $purchase->grand_total = $total;
        $purchase->save();
    }

    private function updatePurchasePayments($purchase, array $payments)
    {
        $totalPaid = $purchase->payments()->sum('amount');
        $newPaymentsTotal = array_sum(array_column($payments, 'amount'));

        if ($totalPaid + $newPaymentsTotal > $purchase->grand_total) {
            throw new Exception('The total payment amount exceeds the purchase debt.');
        }

        foreach ($payments as $paymentData) {
            if (isset($paymentData['payment_id'])) {
                $payment = Payment::findOrFail($paymentData['payment_id']);
                $payment->update([
                    'payment_method' => $paymentData['payment_method'],
                    'amount' => $paymentData['amount'],
                    'payment_date' => $paymentData['payment_date'] ?? $payment->payment_date,
                ]);
            } else {
                $purchase->payments()->create([
                    'amount' => $paymentData['amount'],
                    'payment_method' => $paymentData['payment_method'],
                    'payment_date' => $paymentData['payment_date'],
                    'user_id' => $paymentData['user_id'],
                ]);
            }
        }
    }

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

            $purchase->save();

            DB::commit();

            return response()->json(
                [
                    'message' => 'Payments updated successfully',
                    'data' => $purchase->payments,
                ],
                200,
            );
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update payments', 'error' => $e->getMessage()], 500);
        }
    }
}
