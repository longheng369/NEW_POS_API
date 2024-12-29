<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
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
}
