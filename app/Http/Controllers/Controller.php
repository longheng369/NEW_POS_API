<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    function resizeImage($sourcePath, $destinationPath, $newWidth, $newHeight, $maintainAspectRatio = true) {
        // Get the original image dimensions and type
        list($originalWidth, $originalHeight, $imageType) = getimagesize($sourcePath);

        if ($maintainAspectRatio) {
            // Calculate the aspect ratio
            $aspectRatio = $originalWidth / $originalHeight;

            // Adjust the new width and height based on the aspect ratio
            if ($newWidth / $newHeight > $aspectRatio) {
                // Adjust width based on height
                $newWidth = $newHeight * $aspectRatio;
            } else {
                // Adjust height based on width
                $newHeight = $newWidth / $aspectRatio;
            }
        }

        // Create a new blank image with the (adjusted) dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Create a new image resource from the source image based on type
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false; // Unsupported image type
        }

        // Resize the image (whether maintaining aspect ratio or not)
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Save the resized image to the destination path
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $destinationPath);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $destinationPath);
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $destinationPath);
                break;
            default:
                return false; // Unsupported image type
        }

        // Destroy the image resources to free memory
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return true; // Resize successful
    }

    public function uploadTemporaryImage(Request $request)
    {
        // Validate image input
        $request->validate([
            'image' => 'required|image|max:4048', // max size of 4MB
        ]);

        if ($request->hasFile('image')) {
            // Generate a unique image name using time-based approach
            $imageName = time() . '.' . $request->file('image')->extension();

            // Store the image in the public/images folder
            $request->file('image')->storeAs('public/images/', $imageName);

            $imagePath = storage_path('app/public/images/' . $imageName);
            $resizedImagePath800x800 = storage_path('app/public/images/resized_' . $imageName);
            $resizedImagePath150x150 = storage_path('app/public/thumbs/resized_' . $imageName);

            // Resize the image to 800x800
            if ($this->resizeImage($imagePath, $resizedImagePath800x800, 800, 800, false)) {
                // Store resized image path for use
                $validated['image'] = 'resized_' . $imageName;
            } else {
                return back()->withErrors(['image' => 'Failed to resize image.']);
            }

            // Resize the image to 150x150 for thumbnail
            if ($this->resizeImage($imagePath, $resizedImagePath150x150, 150, 150, false)) {
                // Clean up original image after resize
                unlink($imagePath);
            } else {
                return back()->withErrors(['image' => 'Failed to resize image.']);
            }

            // Store the image path in the session for future reference, but ensure it's for a temporary location
            $temporaryImagePath = 'storage/images/resized_' . $imageName;
            $temporaryThumbPath = 'storage/thumbs/resized_' . $imageName;

        }

        // Return the temporary image path as a response
        return response()->json([
            'tempImagePath' => $temporaryImagePath,
            'tempImageThumbPath' => $temporaryThumbPath,
            'originalImageName' => "resized_" . $imageName,
        ]);
    }

    public function deleteTemporaryImage(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|string',
        ]);

        // Check if the image exists before attempting to unlink
        $imageFullPath = storage_path('app/public/images/' . $validated['image']);
        $thumbFullPath = storage_path('app/public/thumbs/' . $validated['image']);

        if (file_exists($imageFullPath)) {
            unlink($imageFullPath); // Delete the image
        }

        if (file_exists($thumbFullPath)) {
            unlink($thumbFullPath); // Delete the thumbnail
        }


        // Return success message
        return response()->json([
            'message' => 'Temporary image deleted successfully.',
        ]);
    }




}
