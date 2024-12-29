<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $brands = Brand::all();
            return response()->json([
                'status' => 200,
                'data' => $brands,
            ],200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Fail to fetch Brands',
                'error' => $e->getMessage(),
            ],500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $brand = Brand::findOrFail($id);
            return response()->json([
                'status' => 200,
                'data' => $brand,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Brand not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to fetch brand',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validatedBrand = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000', 
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
            'website' => 'nullable|url',
            'country' => 'nullable|string|max:100', 
            'status' => 'nullable|boolean',
        ]);

        try {
            // if ($request->hasFile('logo')) {

            //     $imageName = time() . '.' . $request->file('logo')->extension();
            //     $request->file('logo')->storeAs('public/images', $imageName);
            //     $validatedBrand['logo'] = $imageName;
            // }

            if ($request->hasFile('logo')) {
                // Generate a unique name for the image
                $imageName = time() . '.' . $request->file('logo')->extension();
                
                // Store the original image in the public/images directory
                $request->file('logo')->storeAs('public/images', $imageName);
                
                
                // get full path of image
                $imagePath = storage_path('app/public/images/' . $imageName);
                
                // Define the path where the resized image will be saved
                $resizedImagePath = storage_path('app/public/images/resized_' . $imageName);
        
                // Resize the image
                if ($this->resizeImage($imagePath, $resizedImagePath, 500, 350, true)) {
                    $validatedBrand['logo'] = 'resized_' . $imageName; // Save the name of the resized image
                    unlink($imagePath);
                } else {
                    // Handle error if resizing fails
                    return back()->withErrors(['logo' => 'Failed to resize image.']);
                }
            }
        
            
            $brand = Brand::create($validatedBrand); 
            return response()->json([
                'status' => 201,
                'message' => 'Brand created successfully',
                'data' => $brand,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to create brand',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Fetch the brand by ID, or fail with a 404 error if not found
            $brand = Brand::findOrFail($id);

            // Validate the incoming request
            $validatedBrand = $request->validate([
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
                'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'website' => 'nullable|string',
                'country' => 'nullable|string|max:100',
                'status' => 'nullable|boolean',
                'delete_logo' => 'nullable|boolean', // New field to handle logo deletion
            ]);

            // Check if the logo should be deleted
            if ($request->has('delete_logo') && $request->delete_logo) {
                // Delete the existing logo file if it exists
                if ($brand->logo) {
                    $existingLogoPath = storage_path('app/public/images/' . $brand->logo);
                    if (file_exists($existingLogoPath)) {
                        unlink($existingLogoPath); // Delete the existing logo file
                    }
                    $validatedBrand['logo'] = null; // Set logo to null since it’s deleted
                }
            }

            // Handle new logo upload and resizing
            if ($request->hasFile('logo')) {
                // Generate a unique name for the image
                $imageName = time() . '.' . $request->file('logo')->extension();

                // Store the original image in the public/images directory
                $request->file('logo')->storeAs('public/images', $imageName);

                // Define the path to the stored image
                $imagePath = storage_path('app/public/images/' . $imageName);

                // Define the path where the resized image will be saved
                $resizedImagePath = storage_path('app/public/images/resized_' . $imageName);

                // Resize the image and handle errors
                if ($this->resizeImage($imagePath, $resizedImagePath, 500, 350, true)) {
                    // Remove the original image after resizing
                    unlink($imagePath);
                    $validatedBrand['logo'] = 'resized_' . $imageName; // Save the name of the resized image
                } else {
                    return response()->json([
                        'status' => 500,
                        'message' => 'Failed to resize image.'
                    ], 500);
                }
            }

            // Update the brand with the validated data
            $brand->update($validatedBrand);

            return response()->json([
                'status' => 200,
                'message' => 'Brand updated successfully',
                'data' => $brand,
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Handle the case where the brand is not found
            return response()->json([
                'status' => 404,
                'message' => 'Brand not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            // Log and return general errors
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update brand',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    public function destroy($id): JsonResponse
    {
        try {
            $brand = Brand::findOrFail($id);
            Storage::delete('public/images/' . $brand->logo);
            $brand->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Brand with ID: ' . $id . ' has been deleted successfully!',
            ], 200);
    
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Brand not found!',
                'error' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete brand!',
                'error' => $e->getMessage(),
            ], 500);
        }
    } 
}
