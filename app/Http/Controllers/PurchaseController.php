<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Unit;
use App\Models\Product;
use App\Models\Variant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\UnitConversionService;

class PurchaseController extends Controller
{
    //
    protected $unitConversionService;

    public function __construct(UnitConversionService $unitConversionService)
    {
        $this->unitConversionService = $unitConversionService;
    }



    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'supplier_id' => 'required|exists:suppliers,id',
    //         'user_id' => 'required|exists:users,id',
    //         'tax' => 'nullable|numeric|min:0',
    //         'discount' => 'nullable|numeric|min:0',
    //         'status' => 'nullable|string|in:pending,completed,canceled',
    //         'grand_total' => 'required|numeric|min:0',
    //         'notes' => 'nullable|string|max:500',
    //         'items' => 'required|array|min:1',
    //         'items.*.variant_id' => 'required|exists:variants,id',
    //         'items.*.product_id' => 'required|exists:products,id',
    //         'items.*.unit_id' => 'required|exists:units,id',
    //         'items.*.quantity' => 'required|integer|min:1',
    //         'items.*.unit_price' => 'required|numeric|min:0',
    //         'items.*.discount' => 'nullable|numeric|min:0|max:100',
    //         'items.*.expiration_date' => 'nullable|date|after_or_equal:today',
    //         'items.*.batch_number' => 'nullable|string|max:255'
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         // Create the purchase record
    //         $purchase = Purchase::create($request->only(['supplier_id', 'user_id', 'tax', 'discount', 'status', 'notes']));

    //         $total = 0;

    //         // Loop through items and calculate subtotal for each
    //         foreach ($validated['items'] as $item) {

    //             $product = Product::findOrFail($item['product_id']);

    //             $variant = Variant::findOrFail($item['variant_id']);

    //             $unit_id = $product->unit_id ?? $item['unit_id'];

    //             $quantityInBaseUnit = $this->unitConversionService->convertToBaseUnit(
    //                 $product->id,
    //                 $unit_id,
    //                 $item['quantity']
    //             );

    //             // if unit id user input = base unit exist in product
    //             if($product->base_unit_id === $unit_id){
    //                 $price_per_piece = $variant->costing;
    //                 $unit_price = $variant->costing;
    //             }
    //             // if unit id which already exist in product equal unit user input
    //             elseif ($product->unit_id === $item['unit_id']) {

    //                 $conversionFactor = $product->conversion_factor ?: 1;
    //                 $price_per_piece = $item['unit_price'] / $conversionFactor;
    //             } elseif (!empty($item['unit_id'])) {
    //                 // Find the unit and handle cases where the unit might not exist
    //                 $unit = Unit::find($item['unit_id']);
    //                 if ($unit && $unit->conversion_factor > 0) {
    //                     $price_per_piece = $item['unit_price'] / $unit->conversion_factor;
    //                 }
    //             }

    //             $discount = $item['discount'] ?? 0;



    //             $unit_price = $item['unit_price'];

    //             $quantity = $item['quantity'];

    //             // Calculate the subtotal: quantity * unit_price - (discount percentage)
    //             $subtotal = $quantity * $unit_price * (1 - $discount / 100);

    //             $purchaseItemData = [
    //                 'purchase_id' => $purchase->id,
    //                 'variant_id' => $item['variant_id'],
    //                 'unit_id' => $item['unit_id'],
    //                 'quantity' => $quantityInBaseUnit,
    //                 'unit_price' => $unit_price,
    //                 'discount' => $discount,
    //                 'subtotal' => $subtotal,
    //                 'price_per_piece' => $price_per_piece,
    //                 'expiration_date' => $item['expiration_date'] ?? null,
    //                 'batch_number' => $item['batch_number'] ?? null,
    //             ];

    //             // Create the purchase item
    //             PurchaseItem::create($purchaseItemData);

    //             // Update total
    //             $total += $subtotal;

    //             // Update stock for the variant
    //             $variant = Variant::find($item['variant_id']);
    //             $variant->stock += $quantity; // Adjust stock
    //             $variant->save();
    //         }

    //         // Update the grand total in the purchase record
    //         $purchase->grand_total = $total;
    //         $purchase->save();

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Purchase created successfully',
    //             'data' => $purchase
    //         ], 201);

    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['message' => 'Failed to create purchase', 'error' => $e->getMessage()], 500);
    //     }
    // }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'user_id' => 'required|exists:users,id',
            'tax_rate' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:pending,completed,canceled',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:variants,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
            'items.*.expiration_date' => 'nullable|date|after_or_equal:today',
            'items.*.batch_number' => 'nullable|string|max:255'
        ]);

        DB::beginTransaction();

        try {
            // Create the purchase record
            $purchase = Purchase::create($request->only(['supplier_id', 'user_id', 'tax', 'discount', 'status', 'notes']));

            $total = 0;

            // Loop through items and calculate subtotal for each
            foreach ($validated['items'] as $item) {

                $variant = Variant::where('id', $item['variant_id'])
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (!$variant) {
                    throw new Exception("The variant ID {$item['variant_id']} does not belong to the product ID {$item['product_id']}.");
                }

                // Fetch product and unit
                $product = Product::findOrFail($item['product_id']);
                $unit = Unit::findOrFail($item['unit_id']);

                // $unit = Unit::findOrFail($item['unit_id']);

                // Convert quantity to base unit
                $quantityInBaseUnit = $this->unitConversionService->convertToBaseUnit(
                    $product->id,
                    $item['unit_id'],
                    $item['quantity']
                );

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

                // Calculate subtotal
                $discount = $item['discount'] ?? 0;
                $quantity = $item['quantity'];
                $subtotal = $quantity * $unit_price * (1 - $discount / 100);

                // Prepare purchase item data
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

                // Create purchase item and update total
                PurchaseItem::create($purchaseItemData);
                $total += $subtotal;

                // Update stock for the variant
                $variant->stock += $quantityInBaseUnit;
                $variant->save();
            }


            // Update the grand total in the purchase record
            $purchase->grand_total = $total;
            $purchase->save();

            DB::commit();

            return response()->json([
                'message' => 'Purchase created successfully',
                'data' => $purchase
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create purchase', 'error' => $e->getMessage()], 500);
        }
    }


}
