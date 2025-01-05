<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    //



   public function listProductWithDifferentVariant()
   {
      $products = Product::With(['category', 'supplier:id,name,company_name', 'brand:id,name', 'warehouse', 'baseUnit','variants'])
                           ->whereHas('category', function ($query) {
                              $query->where('status', true); // Ensure category is active
                           })
                           ->orderBy('created_at', 'desc')
                           ->get();

      return response()->json($products, 200);

   }

   public function ProductSuggestionInPurchase()
   {
    $products = Product::with(['baseUnit:id,name', 'variants:id,product_id,name,costing'])
    ->select('id', 'name', 'base_unit_id') // Include 'base_unit_id' for the relationship
    ->get();

       return response()->json($products, 200);
   }


   public function index()
   {
      $variants = Variant::with([
         'product' => function ($query) {
               $query->select('id', 'code', 'name', 'type', 'image', 'status', 'barcode_symbology', 'category_id', 'supplier_id', 'brand_id', 'warehouse_id', 'base_unit_id', 'promotion', 'discount', 'tax_rate', 'details', 'is_perishable', 'created_at', 'updated_at')
                     ->with([
                        'category:id,name',
                        'supplier:id,name',
                        'brand:id,name',
                        'warehouse:id,name',
                        'baseUnit:id,name'
                     ]);
         }
      ])->select('id', 'name','code', 'costing', 'price', 'stock', 'product_id')
         ->get()
         ->map(function ($variant) {
            return [
               'variant_id' => $variant->id,
               'variant_name' => $variant->name,
               'variant_code' => $variant->code,
               'variant_costing' => $variant->costing,
               'variant_price' => $variant->price,
               'variant_stock' => $variant->stock,
               'product_id' => $variant->product->id,
               'product_code' => $variant->product->code,
               'product_image' => $variant->product->image,
               'product_name' => $variant->product->name,
               'product_type' => $variant->product->type,
               'product_status' => $variant->product->status,
               'barcode_symbology' => $variant->product->barcode_symbology,
               'category' => $variant->product->category,
               'supplier' => $variant->product->supplier,
               'brand' => $variant->product->brand,
               'warehouse' => $variant->product->warehouse,
               'base_unit' => $variant->product->baseUnit,
               'promotion' => $variant->product->promotion,
               'discount' => $variant->product->discount,
               'tax_rate' => $variant->product->tax_rate,
               'details' => $variant->product->details,
               'is_perishable' => $variant->product->is_perishable,
               'created_at' => $variant->product->created_at,
               'updated_at' => $variant->product->updated_at,
            ];
         });

      return response()->json($variants);

   }

    public function show($id)
    {
        $products = Product::With(['category', 'supplier:id,name,company_name', 'brand:id,name', 'warehouse', 'baseUnit','variants'])
                            ->whereHas('category', function ($query) {
                                $query->where('status', true); // Ensure category is active
                            })
                            ->findOrFail($id);

        return response()->json($products, 200);
    }

   public function store(Request $request)
   {
      $validated = $request->validate([
         'type' => 'required|in:standard,service',
         'code' => 'required|string|unique:products,code',
         'name' => 'required|string|max:255',
         'status' => 'boolean',
         'image' => 'nullable|string',
         'barcode_symbology' => 'nullable|string|max:255',
         'category_id' => 'required|exists:categories,id',
         'supplier_id' => 'nullable|exists:suppliers,id',
         'brand_id' => 'nullable|exists:brands,id',
         'warehouse_id' => 'nullable|exists:warehouses,id',
         'base_unit_id' => 'nullable|exists:units,id',
         'unit_id' => 'nullable|exists:units,id',
         'conversion_factor' => 'nullable|integer',
         'alert_quantity' => 'nullable|integer',
         'promotion' => 'nullable|boolean',
         'discount' => 'required_if:promotion,true|numeric|min:0|max:100',
         'start_date' => 'nullable|required_if:promotion,true|date',
         'end_date' => 'nullable|required_if:promotion,true|date|after:start_date',
         'tax_rate' => 'nullable|numeric|max:100',
         'details' => 'nullable|string',
         'is_perishable' => 'required|boolean',
         'variants' => 'required|array|min:1',
         'variants.*.name' => 'required|string',
         'variants.*.code' => 'required|string',
         'variants.*.costing' => 'required|numeric',
         'variants.*.price' => 'required|numeric',
         'variants.*.unit_id' => 'nullable|exists:units,id',
         'variants.*.conversion_factor' => 'nullable|numeric',

      ]);

    //   if ($request->hasFile('image')) {
    //      $imageName = time() . '.' . $request->file('image')->extension();
    //      $request->file('image')->storeAs('public/images/', $imageName);

    //      $imagePath = storage_path('app/public/images/' . $imageName);
    //      $resizedImagePath800x800 = storage_path('app/public/images/resized_' . $imageName);
    //      $resizedImagePath150x150 = storage_path('app/public/thumbs/resized_' . $imageName);

    //      if ($this->resizeImage($imagePath, $resizedImagePath800x800, 800, 800, false)) {
    //            $validated['image'] = 'resized_' . $imageName;
    //      } else {
    //            return back()->withErrors(['image' => 'Failed to resize image.']);
    //      }

    //      if ($this->resizeImage($imagePath, $resizedImagePath150x150, 150, 150, false)) {
    //            unlink($imagePath);
    //      } else {
    //            return back()->withErrors(['image' => 'Failed to resize image.']);
    //      }
    //   }

      if($request->has('promotion') && $validated['promotion'] === false){
         $validated['discount'] = 0;
         $validated['start_date'] = null;
         $validated['end_date'] = null;
      }


      DB::beginTransaction();

      try {
         $product = Product::create(Arr::except($validated, ['variants']));

         foreach ($validated['variants'] as $variant) {
               $finalSellingPrice = $variant['price'];
               $previousPrice = null;

               if ($validated['promotion'] && $validated['discount']) {
                  $previousPrice = $variant['price'];
                  $finalSellingPrice = $variant['price'] * (1 - ($validated['discount'] / 100));
               }

               Variant::create([
                  'product_id' => $product->id,
                  'name' => $variant['name'],
                  'code' => $variant['code'],
                  'costing' => $variant['costing'],
                  'price' => $finalSellingPrice,
                  'previous_price' => $previousPrice,
                  'unit_id' => $variant['unit_id'] ?? null,
                  'conversion_factor' => $variant['conversion_factor'] ?? null,
                  'alert_quantity' => $validated['alert_quantity'] ?? 0,
               ]);
         }

         DB::commit();
         return response()->json(['message' => 'Product created successfully'], 201);
      } catch (Exception $e) {
         DB::rollBack();
         return response()->json(['error' => 'Failed to create product', 'details' => $e->getMessage()], 500);
      }
   }


    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:standard,service',
            'code' => 'sometimes|string|unique:products,code',
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|boolean',
            'image' => 'sometimes|string',
            'delete_image' => 'sometimes|boolean',
            'barcode_symbology' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'brand_id' => 'sometimes|exists:brands,id',
            'warehouse_id' => 'sometimes|exists:warehouses,id',
            'base_unit_id' => 'sometimes|exists:units,id',
            'unit_id' => 'sometimes|exists:units,id',
            'conversion_factor' => 'sometimes|integer',
            'alert_quantity' => 'sometimes|integer',
            'promotion' => 'sometimes|boolean',
            'discount' => 'required_if:promotion,true|numeric|min:0|max:100',
            'start_date' => 'sometimes|required_if:promotion,true|date',
            'end_date' => 'sometimes|required_if:promotion,true|date|after:start_date',
            'tax_rate' => 'sometimes|numeric|max:100',
            'details' => 'sometimes|string',
            'is_perishable' => 'sometimes|boolean',
            'variants' => 'sometimes|array|min:1',
            'variants.*.id' => 'nullable|exists:variants,id',
            'variants.*.name' => 'sometimes|string',
            'variants.*.code' => 'sometimes|string',
            'variants.*.costing' => 'sometimes|numeric',
            'variants.*.price' => 'sometimes|numeric',
            'variants.*.unit_id' => 'sometimes|exists:units,id',
            'variants.*.conversion_factor' => 'sometimes|numeric',
            'delete_variants' => 'nullable|array', // IDs of variants to delete
            'delete_all_variants' => 'nullable|boolean', // Flag to delete all variants
         ]);

        DB::beginTransaction();

        try {
            // Find the product
            $product = Product::findOrFail($id);

            // Handle image update
            // if ($request->hasFile('image')) {
            //     $imageName = time() . '.' . $request->file('image')->extension();
            //     $request->file('image')->storeAs('public/images', $imageName);

            //     $imagePath = storage_path('app/public/images/' . $imageName);
            //     $resized800x800 = storage_path('app/public/images/resized_' . $imageName);
            //     $resized150x150 = storage_path('app/public/thumbs/resized_' . $imageName);

            //     if ($this->resizeImage($imagePath, $resized800x800, 800, 800, false)) {
            //         $validated['image'] = "resized_" . $imageName;
            //     }

            //     if ($this->resizeImage($imagePath, $resized150x150, 150, 150, false)) {
            //         unlink($imagePath);
            //     } else {
            //         throw new Exception('Failed to resize image.');
            //     }

            //     if (!empty($product->image) && file_exists(storage_path('app/public/images/' . $product->image))) {
            //         unlink(storage_path('app/public/images/' . $product->image));
            //         unlink(storage_path('app/public/thumbs/' . $product->image));
            //     }
            // } elseif ($request->delete_image) {
            //     if (!empty($product->image) && file_exists(storage_path('app/public/images/' . $product->image))) {
            //         unlink(storage_path('app/public/images/' . $product->image));
            //         unlink(storage_path('app/public/thumbs/' . $product->image));
            //     }
            //     $validated['image'] = null;
            // } else {
            //     $validated['image'] = $product->image;
            // }

            if($request->delete_image){
                if(!empty($product->image) && file_exists(storage_path('app/public/images/' . $product->image))){
                    unlink(storage_path('app/public/images/' . $product->image));
                    unlink(storage_path('app/public/thumbs/' . $product->image));
                    $validated['image'] = null;
                }

            }
            // Update product fields
            $product->fill($validated);
            $product->save();

             // Handle variant deletions
            if ($request->boolean('delete_all_variants')) {
                // Delete all variants
                $product->variants()->delete();
            } elseif ($request->has('delete_variants')) {
                // Delete specific variants by ID
                $product->variants()->whereIn('id', $validated['delete_variants'])->delete();
            }

            // Handle updating specific variants or creating new ones
            if ($request->has('variants')) {
                foreach ($validated['variants'] as $variant) {
                    // Check if the variant has an 'id' field
                    if (isset($variant['id'])) {
                        // Update the specific variant by ID
                        $existingVariant = Variant::find($variant['id']);
                        if ($existingVariant) {
                            $existingVariant->update([
                                'name' => $variant['name'],
                                'code' => $variant['code'],
                                'costing' => $variant['costing'],
                                'price' => $variant['price'],
                                'unit_id' => $variant['unit_id'] ?? null,
                                'conversion_factor' => $variant['conversion_factor'] ?? null,
                            ]);
                        }
                    } else {
                        // If there's no ID, create a new variant
                        Variant::create([
                            'product_id' => $product->id,
                            'name' => $variant['name'],
                            'code' => $variant['code'],
                            'costing' => $variant['costing'],
                            'price' => $variant['price'],
                            'unit_id' => $variant['unit_id'] ?? null,
                            'conversion_factor' => $variant['conversion_factor'] ?? null,
                        ]);
                    }
                }
            }
            DB::commit();

            return response()->json(['message' => 'Product updated successfully', 'product' => $product], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update product', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully!'], 200);
    }

    public function destroys(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
        ]);

        $ids = $request->input('ids');

        Product::whereIn('id', $ids)->delete();

        return response()->json([
            'message' => 'Products deleted successfully',
        ],200);
    }
}
