<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\Product;
use App\Models\Variant;
use App\Models\Purchase;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use App\Models\DailyReference;
use Illuminate\Support\Facades\DB;
use App\Services\UnitConversionService;

class SaleController extends Controller
{
    protected $unitConversionService;

    public function __construct(UnitConversionService $unitConversionService)
    {
        $this->unitConversionService = $unitConversionService;
    }
    //

    public function index()
    {
        $sales = Sale::with(['items.variant'])->orderBy('created_at', 'desc')->get();
        return response()->json($sales, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'tax_rate' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|string',
            'notes' => 'nullable|string|max:555',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'required|exists:variants,id',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0|max:100', 
            'payments' => 'nullable|array',
            'payments.*.amount' => 'required_if:payments,true|numeric|min:0',
            'payments.*.payment_method' => 'required_if:payments,true|string',
            'payments.*.payment_date' => 'nullable|date'
        ]);

        DB::beginTransaction();

        try {
            // Generate a unique reference number for today
            $today = now()->format('Y-m-d');
            $dailyReference = DailyReference::firstOrCreate(
                ['date' => $today],
                ['reference_count' => 0]
            );

            $dailyReference->increment('reference_count');

            $saleData = $request->only(['customer_id', 'user_id', 'tax_rate', 'discount', 'status', 'notes']);
            $saleData['reference_no'] = 'SALE-' . strtoupper($today . '-' . str_pad($dailyReference->reference_count, 4, '0', STR_PAD_LEFT));

            $sale = Sale::create($saleData);

            $grand_total = 0;

            // Check stock availability first
            foreach ($validated['items'] as $item) {
                $variant = Variant::findOrFail($item['variant_id']);
                $quantityInBaseUnit = $this->unitConversionService->convertToBaseUnit(
                    $item['product_id'],
                    $item['unit_id'],
                    $item['quantity']
                );

                if ($variant->stock < $quantityInBaseUnit) {
                    DB::rollBack();
                    return response()->json(['message' => 'Insufficient stock for ' . $variant->name], 400);
                }
            }

            // Process sale items and update stock
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
                
                $quantityInBaseUnit = $this->unitConversionService->convertToBaseUnit(
                    $item['product_id'],
                    $item['unit_id'],
                    $item['quantity']
                );

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

                if (empty($unit_price)) {
                    $unit_price = $price_per_piece;
                }

                $discount = $item['discount'] ?? 0;
                $subtotal = $item['quantity'] * $unit_price * (1 - $discount / 100);

                $saleItemData = [
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity' => $quantityInBaseUnit,
                    'unit_price' => $unit_price,
                    'discount' => $discount,
                    'subtotal' => $subtotal,
                    'price_per_piece' => $price_per_piece,
                ];

                SaleItem::create($saleItemData);
                $grand_total += $subtotal;

                // Deduct stock
                $variant->stock -= $quantityInBaseUnit;
                $variant->save();
            }

            $sale->grand_total = $grand_total;
            $sale->save();

            DB::commit();

            return response()->json([
                'message' => 'Sale created successfully',
                'data' => $sale
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sale', 'error' => $e->getMessage()], 500);
        }
    }


}
