<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class WarehouseController extends Controller
{
    public function index ()
    {
        $warehouses = DB::table('warehouses')
            ->leftJoin('users', 'warehouses.manager_id', '=', 'users.id')
            // ->where('warehouses.status', 1)
            ->where(function ($query) {
                $query->where('users.role', 'manager')
                    ->orWhereNull('warehouses.manager_id');
            })
            ->select('warehouses.id','warehouses.name','warehouses.location','warehouses.contact_number','warehouses.status','warehouses.notes', 'users.name as manager') 
            ->get();

        try {
            return response()->json([
                "status" => 200,
                "data" => $warehouses
            ],200);
        } catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "message" => "Fail to fetch warehouse",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function show($id) {
        try {
            $warehouse = DB::table('warehouses')
                ->leftJoin('users', 'warehouses.manager_id', '=', 'users.id')
                ->where('warehouses.status', 1)
                ->where('warehouses.id',$id)
                ->where(function ($query) {
                    $query->where('users.role', 'manager')
                        ->orWhereNull('warehouses.manager_id');
                })
            ->select('warehouses.*', 'users.name as manager') 
            ->first();
            return response()->json([
                "status" => 200,
                "data" => $warehouse
            ],200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                "status" => 500,
                "message" => "Product Not Found",
                "error" => $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "message" => "Fail to find warehouse",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request) 
    {
        $warehouseValidation = $request->validate([
            "name" => "required|string|max:55",
            "location" => "nullable|string",
            "capacity" => "nullable|integer",
            "manager_id" => "nullable|exists:users,id",
            "contact_number" => "nullable|string",
            "status" => "nullable|boolean",
            "notes" => "nullable|string"
        ]);

        try {
            $warehouse = Warehouse::create($warehouseValidation);
            return response()->json([
                "status" => 201,
                "message" => "Warehouse create successfull",
                "data" => $warehouse
            ],201);
        } catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "error" => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $warehouseValidation = $request->validate([
            "name" => "sometimes|nullable|string|max:55",
            "location" => "sometimes|nullable|string",
            "capacity" => "sometimes|numeric",
            "manager_id" => "sometimes|nullable|exists:users,id",
            "contact_number" => "sometimes|nullable|string",
            "status" => "sometimes|nullable|boolean",
            "notes" => "sometimes|nullable|string"
        ]);
        try {
            $warehouse = Warehouse::findOrFail($id);

            $warehouse->update($warehouseValidation);

            return response()->json([
                "status" => 200,
                "message" => "warehouse update successfull",
                "data" => $warehouse
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "error" => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $warehouse = Warehouse::findOrFail($id);
            $warehouse->delete();
            return response()->json([
                "status" => 201,
                "message" => "warehouse deleted successful.",
            ]);
        } catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "error" => $e->getMessage(),
            ]);
        }
    }
}
