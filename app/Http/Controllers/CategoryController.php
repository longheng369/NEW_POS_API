<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    // Display a listing of the categories
    public function index(): JsonResponse
    {
        try {
            $categories = Category::all();
            return response()->json(['status' => 200, 'data' => $categories], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while fetching categories',
                'error' => $e->getMessage(),
            ], 500); 
        }
    }

    public function store(Request $request)
    {
        $validatedCategory = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'sometimes|boolean',
        ]);
        
        $validatedCategory['status'] = $validatedCategory['status'] ?? true; 

        try {
            if ($request->hasFile('image')) {
                $imageName = time() . '.' . $request->file('image')->extension();
                $request->file('image')->storeAs('public/images', $imageName);
                $validatedCategory['image'] = $imageName;
            }
            $category = Category::create($validatedCategory);
            return response()->json(['status' => 201, 'message' => 'Category created successfully', 'data' => $category], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Failed to create category', 'error' => $e->getMessage()], 500);
        }        
    }

    // Display the specified category
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json(['status' => 200, 'data' => $category], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 404, 'message' => 'Category not found', 'error' => $e->getMessage()], 404);
        }
    }

    // Update the specified category in storage
    public function update(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
            $validatedCategory = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'status' => 'sometimes|boolean',
            ]);

            $validatedCategory['status'] = $validatedCategory['status'] ?? true; 

            if ($request->hasFile('image')) {
                $imageName = time() . '.' . $request->file('image')->extension();
                $request->file('image')->storeAs('public/images', $imageName);
                $validatedCategory['image'] = $imageName;
            }
            $category->update($validatedCategory);
            return response()->json(['status' => 200, 'message' => 'Category updated successfully', 'data' => $category], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Failed to update category', 'error' => $e->getMessage()], 500);
        } 
    }

    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();
            return response()->json(['status' => 200, 'message' => 'Category with ID: ' . $id . ' has been deleted successfully!'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Failed to delete category', 'error' => $e->getMessage()], 500);
        }
    }
}
