<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        try {
            $supplier = Supplier::all();
            return response()->json([
                "status" => 200,
                "data" => $supplier
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "error" => $e->getMessage(),
            ],500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validationSupplier = $request->validate([
                'name' => 'required|string|max:255', 
                'company_name' => 'nullable|string|max:255', 
                'contact_person' => 'nullable|string|max:255', 
                'phone_number' => 'required|string|max:20', 
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:255',
                'website' => 'nullable|url|max:255', 
                'notes' => 'nullable|string', 
                'status' => 'nullable|boolean',
            ]);

            $supplier = Supplier::create($validationSupplier);
            return response()->json([
                "status" => 200,
                "message" => "supplier created successful",
                "data" => $supplier,
            ],200);
        } catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "error" => $e->getMessage(),
            ],500);
        }
    }

    public function show($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            return response()->json([
                "status" => 200,
                "data" => $supplier
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "status" => 404,
                "error" => $e->getMessage(),
            ], 404);
        }
        catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "error" => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validationSupplier = $request->validate([
                'name' => 'required|string|max:255', 
                'company_name' => 'nullable|string|max:255', 
                'contact_person' => 'nullable|string|max:255', 
                'phone_number' => 'required|string|max:20', 
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:255',
                'website' => 'nullable|url|max:255', 
                'notes' => 'nullable|string', 
                'status' => 'nullable|boolean',
            ]);

            $supplier = Supplier::findOrFail($id);
            $supplier->update($validationSupplier);
            return response()->json([
                "status" => 200,
                "data" => $supplier,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "status" => 404,
                "error" => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "error" => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();
            return response()->json([
                "status" => 200,
                "message" => "Supplier deleted successful"
            ],200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "status" => 404,
                "error" => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "error" => $e->getMessage(),
            ], 500);
        } 
    }
}
