<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {
        try {
            $units = Unit::all();
            $suppliers = Supplier::select('id', 'name')
                ->get();
            $warehouses = Warehouse::select('id', 'name')
                ->where('status', 1)
                ->get();

            $categories = Category::select('id', 'name')
                ->where('status', 1)
                ->get();

            $users = User::select('*')
                ->where('status', 1)
                ->get();
            

            $brands = Brand::select('id', 'name')
                ->get();

            return response()->json([
                "status" => 200,
                "units" => $units,
                "users" => $users,
                "suppliers" => $suppliers,
                "warehouses" => $warehouses,
                "categories" => $categories,
                "brands" => $brands
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "status" => 500,
                "error" => $e->getMessage(),
            ], 500);
        }

        
    }
}
