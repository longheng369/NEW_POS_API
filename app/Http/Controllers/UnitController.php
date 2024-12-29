<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

use function PHPSTORM_META\map;

class UnitController extends Controller
{
    public function index()
    {
        try {
            $units = Unit::with(['baseUnit:id,name'])->get();
            return response()->json([
                "status" => 200,
                "data" => $units,
            ],  200);
        } catch (Exception $e) {
            return response()->json([
               "status" => 500,
               "error" => $e->getMessage(),
            ], 500);
        }
    } 

    public function store(Request $request)
    {
        $validationUnit = $request->validate([
            "name" => "required|string|max:55",
            "code" => "required|string|max:55",
            "base_unit_id" => 'nullable|integer',
            "conversion_factor" => "required_with:base_unit_id|numeric|nullable"
        ]);
        
        try {
            $unit = Unit::create($validationUnit);
            return response()->json([
                "message" => "unit created successful",
                "data" => $unit
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "error" => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $unit = Unit::findOrFail($id);
            return response()->json([
                "status" => 200,
                "data" => $unit,
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

    public function update(Request $request, $id)
    {
        $validationUnit = $request->validate([
            "name" => "sometimes|string|max:55",
            "code" => "sometimes|string|max:55",
            "base_unit_id" => 'sometimes|nullable|integer',
            "conversion_factor" => "sometimes|required_with:base_unit_id|numeric|nullable"
        ]);

        try {
            $unit = Unit::findOrFail($id);

            $unit->update($validationUnit);

            return response()->json([
                "status" => 200,
                "message" => "unit updated successful",
                "data" => $unit,
            ]);
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
            $unit = Unit::findOrFail($id);
            $unit->delete();
            return response()->json([
                "status" => 200,
                "message" => "unit deleted successful."
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
